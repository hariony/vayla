<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le portrait du propriétaire, et son adresse exacte.
 *
 * **Le portrait n'est pas une photo d'annonce, et ne passe pas par la table
 * `photos`.** Celle-ci porte les photographies de Commons et celles des
 * logements : crédit obligatoire, trois résolutions, et `PhotoFilesTest` la
 * surveille fichier par fichier — un portrait y entrerait comme un orphelin
 * sans auteur. Une colonne suffit : un portrait appartient à un compte, il
 * n'est jamais partagé entre deux, et il n'a pas de légende.
 *
 * **L'adresse exacte ne s'affiche nulle part côté public.** Elle sert à deux
 * choses, et l'écran le dit : la facture de fin de mois, qui doit désigner
 * quelqu'un pour être payable, et la vérification — le correspondant local qui
 * passe voir un logement doit savoir où aller. La ville reste à côté : elle
 * est approximative, se remplit à l'appel, et suffit à tout le reste.
 *
 * Les deux sont **nullables** : aucun compte existant ne devient invalide, et
 * l'inscription ne les demande pas — un formulaire de six champs devant
 * quelqu'un qui n'a encore rien reçu de Vayla est un formulaire qu'on quitte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            // La clé du fichier, sans son palier ni son extension —
            // `resources/js/Support/portrait.js` compose l'adresse.
            $table->string('portrait')->nullable()->after('city');
            $table->string('address', 200)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('owners', fn (Blueprint $table) => $table->dropColumn(['portrait', 'address']));
    }
};
