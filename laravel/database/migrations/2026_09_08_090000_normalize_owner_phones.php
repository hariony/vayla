<?php

use App\Support\Telephone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le numéro devient une donnée normalisée, et sa possession se retient.
 *
 * **Pourquoi normaliser.** Le téléphone est la clé de connexion du
 * propriétaire, et la comparaison se faisait sur les **neuf derniers
 * chiffres** de la saisie. Ça marchait, mais ça reposait sur une coïncidence
 * de longueur plutôt que sur une règle : deux numéros de pays différents
 * finissant pareil auraient ouvert le même compte. En stockant l'E.164, on
 * compare des numéros.
 *
 * **`phone_verified_at` n'invente aucune vérification : il en enregistre une
 * qui existait déjà.** Vayla envoie le lien d'accès sur le WhatsApp du
 * propriétaire ; si le lien arrive et qu'il s'en sert pour ouvrir son compte,
 * le numéro est prouvé — c'est exactement ce que fait un code à usage unique,
 * sans fournisseur de SMS ni coût par message. Il ne manquait que la colonne
 * pour le noter.
 *
 * Les lignes de démonstration sont converties ici : les laisser mi-formatées
 * ferait échouer une connexion sur deux, et ce serait invisible en test.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
        });

        foreach (DB::table('owners')->get(['id', 'phone', 'mobile_money']) as $owner) {
            $champs = [];

            if ($numero = Telephone::depuis($owner->phone)) {
                $champs['phone'] = $numero->e164();
            }

            // `mobile_money` est un numéro lui aussi : c'est là que le
            // propriétaire pousse son règlement, et un numéro mal formé s'y
            // découvre le jour de la facture.
            if ($owner->mobile_money && $mm = Telephone::depuis($owner->mobile_money)) {
                $champs['mobile_money'] = $mm->e164();
            }

            if ($champs !== []) {
                DB::table('owners')->where('id', $owner->id)->update($champs);
            }
        }
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn('phone_verified_at');
        });
    }
};
