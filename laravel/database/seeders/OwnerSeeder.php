<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\Owner;
use App\Support\Telephone;
use Illuminate\Database\Seeder;

/**
 * Les propriétaires de démonstration.
 *
 * Ils existent parce que le modèle économique l'exige : une facture
 * mensuelle a besoin d'un destinataire, et jusqu'ici une annonce
 * n'appartenait à personne.
 *
 * Les numéros sont des **numéros de test réservés** (préfixe 034 00 000 xx),
 * jamais des numéros réels : un numéro plausible dans un jeu de données
 * finit toujours par être appelé.
 */
class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        // **Un domaine `.test`, réservé par la RFC 6761 et non routable.**
        // Ces adresses sont des identifiants de connexion : sur `example.com`
        // elles entrent en collision avec ce qu'un humain tape en essayant le
        // produit — c'est arrivé — et surtout un code de démonstration ne doit
        // jamais pouvoir partir vers une boîte qui existe.
        $annonces = [
            'Hanta Rakotoarisoa' => [
                'email' => 'hanta@demo.vayla.test',
                'phone' => '+261 34 00 000 01', 'city' => 'Nosy Be',
                'mobile_money' => '+261 34 00 000 01', 'operator' => 'MVola',
                'listings' => ['villa-ambatoloaka', 'bungalow-madirokely'],
            ],
            'Jean-Claude Ranaivo' => [
                'email' => 'jean-claude@demo.vayla.test',
                'phone' => '+261 34 00 000 02', 'city' => 'Antananarivo',
                'mobile_money' => '+261 34 00 000 02', 'operator' => 'MVola',
                'listings' => ['maison-itasy', 'studio-thermal'],
            ],
            'Voahangy Andriamalala' => [
                'email' => 'voahangy@demo.vayla.test',
                'phone' => '+261 34 00 000 03', 'city' => 'Majunga',
                'mobile_money' => '+261 32 00 000 03', 'operator' => 'Orange Money',
                'listings' => ['front-de-mer-amborovy'],
            ],
            'Solofo Rabemananjara' => [
                'email' => 'solofo@demo.vayla.test',
                'phone' => '+261 34 00 000 04', 'city' => 'Antsirabe',
                'mobile_money' => '+261 33 00 000 04', 'operator' => 'Airtel Money',
                'listings' => ['villa-coloniale-antsirabe'],
            ],
            'Nirina Razafy' => [
                'email' => 'nirina@demo.vayla.test',
                'phone' => '+261 34 00 000 05', 'city' => 'Tuléar',
                'mobile_money' => '+261 34 00 000 05', 'operator' => 'MVola',
                'listings' => ['case-ifaty', 'lodge-andasibe'],
            ],
        ];

        foreach ($annonces as $nom => $row) {
            // Les numéros sont stockés en E.164 : c'est la forme de
            // comparaison à la connexion, et la seule qui ne dépende pas de
            // la façon dont quelqu'un a tapé son numéro ce jour-là.
            $numero = Telephone::depuis($row['phone'])?->e164() ?? $row['phone'];

            /*
             * **La clé est le nom, pas le téléphone.** Le numéro est devenu
             * un identifiant de connexion, donc une donnée qui bouge : le
             * jour où il change, `updateOrCreate` ne reconnaît plus la ligne
             * et en crée une seconde — deux propriétaires du même nom, dont
             * un sans logement. C'est arrivé. Le nom, lui, est la clé du
             * tableau ci-dessus : il ne peut pas diverger de ce que le seeder
             * possède.
             */
            $owner = Owner::updateOrCreate(
                ['name' => $nom, 'is_demo' => true],
                [
                    // La clé n'est tirée qu'à la création : la régénérer à
                    // chaque `make seed` invaliderait le lien déjà envoyé au
                    // propriétaire par WhatsApp.
                    'access_key' => Owner::where('name', $nom)->where('is_demo', true)->value('access_key')
                        ?? Owner::nouvelleCle(),
                    // La date de pose ne bouge pas non plus tant que la clé
                    // ne bouge pas : elle sert à répondre à « depuis combien
                    // de temps ce lien circule ? ».
                    'access_key_set_at' => Owner::where('name', $nom)->where('is_demo', true)->value('access_key_set_at')
                        ?? now(),
                    'phone' => $numero,
                    'email' => $row['email'],
                    'city' => $row['city'],
                    'mobile_money' => Telephone::depuis($row['mobile_money'])?->e164() ?? $row['mobile_money'],
                    'mobile_money_operator' => $row['operator'],
                    // Un mot de passe de démonstration, pour que l'écran de
                    // connexion soit utilisable sans passer par le lien. Il
                    // n'est posé qu'à la création : le régénérer à chaque
                    // `make seed` chasserait un propriétaire déjà connecté.
                    'password' => Owner::where('name', $nom)->where('is_demo', true)->value('password')
                        ?? 'vayla-demo-2026',
                    'password_set_at' => Owner::where('name', $nom)->where('is_demo', true)->value('password_set_at')
                        ?? now(),
                    'is_demo' => true,
                ]
            );

            Listing::query()->whereIn('slug', $row['listings'])->update(['owner_id' => $owner->id]);
        }
    }
}
