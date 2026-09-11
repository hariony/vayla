<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le voyageur a un nom, un prénom, et un numéro.
 *
 * **Un champ « nom » unique ne se relit pas.** « RAKOTOBE Jean » est une
 * écriture courante ici, « Jean Rakotobe » l'est ailleurs, et rien ne permet
 * de savoir laquelle on a sous les yeux : l'écran des réservations en avait
 * fait « Bonjour RAKOTOBE », c'est-à-dire un patronyme crié à quelqu'un qu'on
 * voulait accueillir. Deux colonnes lèvent l'ambiguïté à la saisie, une fois
 * pour toutes.
 *
 * **`name` disparaît plutôt que de rester à côté.** Deux endroits où vit le
 * même fait finissent toujours par diverger ; le nom complet est désormais
 * **dérivé** des deux parties par un accesseur du modèle, comme `perks` l'est
 * des équipements marqués. Tout le code qui lit `->name` continue de marcher,
 * et celui qui l'écrit — la connexion sociale, qui reçoit un nom entier —
 * passe par le mutateur qui le sépare.
 *
 * **La reprise coupe au premier espace**, faute de mieux : c'est juste pour
 * « Jean Rakotobe » et faux pour « RAKOTOBE Jean ». Aucune heuristique ne
 * tranchera à notre place, et l'écran du compte permet maintenant de corriger
 * en dix secondes — ce qui n'était pas le cas avant.
 *
 * **Le numéro n'est pas unique.** Côté propriétaire il identifie un compte ;
 * ici il sert seulement à pré-remplir une demande de séjour, et deux personnes
 * d'un même foyer peuvent parfaitement partager une ligne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            // Ce par quoi le propriétaire rappelle le voyageur — le même rôle
            // que sur une réservation, pas un identifiant de connexion.
            $table->string('phone')->nullable()->after('email');
        });

        foreach (DB::table('users')->whereNotNull('name')->get(['id', 'name']) as $ligne) {
            $entier = trim((string) $ligne->name);

            if ($entier === '') {
                continue;
            }

            $morceaux = preg_split('/\s+/', $entier, 2) ?: [];

            DB::table('users')->where('id', $ligne->id)->update([
                'first_name' => $morceaux[0] ?? null,
                'last_name' => $morceaux[1] ?? null,
            ]);
        }

        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('name'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('name')->nullable()->after('id'));

        foreach (DB::table('users')->get(['id', 'first_name', 'last_name']) as $ligne) {
            $entier = trim(($ligne->first_name ?? '').' '.($ligne->last_name ?? ''));

            DB::table('users')->where('id', $ligne->id)->update(['name' => $entier ?: null]);
        }

        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['first_name', 'last_name', 'phone']));
    }
};
