<?php

namespace App\Console\Commands;

use App\Models\Owner;
use App\Services\OwnerKeyService;
use Illuminate\Console\Command;

/**
 * Change la clé d'accès d'un propriétaire, ou de tous.
 *
 * C'est la contrepartie du choix « une clé, pas un mot de passe » : sans
 * moyen de révoquer, un lien qui fuite reste valable pour toujours. La
 * commande est **hors bande** à dessein — jamais un bouton dans l'espace
 * propriétaire, qui serait offert à celui qui a volé le lien.
 *
 *   php artisan vayla:rotate-owner-key "+261 34 00 000 01"
 *   php artisan vayla:rotate-owner-key 0340000001      # la même personne
 *   php artisan vayla:rotate-owner-key --all           # incident, base copiée
 *
 * La confirmation n'est pas de la politesse : se tromper de numéro coupe
 * l'accès d'un propriétaire au moment précis où une demande expire chez lui.
 * On affiche donc **qui** avant de faire quoi que ce soit.
 */
class RotateOwnerKey extends Command
{
    protected $signature = 'vayla:rotate-owner-key
                            {numero? : Numéro de téléphone du propriétaire, quelle qu\'en soit l\'écriture}
                            {--all : Tourne toutes les clés — réservé à l\'incident}
                            {--force : Ne demande pas confirmation}';

    protected $description = 'Change la clé d’accès d’un propriétaire : l’ancien lien cesse aussitôt de fonctionner';

    public function handle(OwnerKeyService $cles): int
    {
        return $this->option('all')
            ? $this->toutes($cles)
            : $this->une($cles);
    }

    private function une(OwnerKeyService $cles): int
    {
        $numero = $this->argument('numero');

        if (! $numero) {
            $this->error('Indiquez le numéro du propriétaire, ou utilisez --all.');

            return self::FAILURE;
        }

        $owner = $cles->trouver($numero);

        if (! $owner) {
            $this->error("Aucun propriétaire ne correspond au numéro « {$numero} ».");

            return self::FAILURE;
        }

        $this->afficher($owner);

        if (! $this->option('force') && ! $this->confirm('Changer sa clé ? L’ancien lien cessera aussitôt de fonctionner.')) {
            $this->line('Rien n’a été changé.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Nouveau lien de connexion, à envoyer sur son WhatsApp :');
        $this->line($cles->tourner($owner));
        $this->newLine();
        $this->comment('L’ancien lien ne fonctionne plus. Son mot de passe, lui, reste valable.');

        return self::SUCCESS;
    }

    private function toutes(OwnerKeyService $cles): int
    {
        $this->warn('Toutes les clés vont changer : chaque lien déjà envoyé cessera de fonctionner,');
        $this->warn('et il faudra les renvoyer un par un. Ne le faites que sur incident.');

        if (! $this->option('force') && ! $this->confirm('Continuer ?', false)) {
            $this->line('Rien n’a été changé.');

            return self::SUCCESS;
        }

        $lignes = $cles->tournerToutes();

        $this->newLine();
        $this->table(
            ['Propriétaire', 'Téléphone', 'Nouveau lien'],
            array_map(fn (array $l) => [$l['owner']->name, $l['owner']->phone, $l['lien']], $lignes),
        );

        $this->comment(count($lignes).' clé(s) changée(s). Aucun ancien lien ne fonctionne plus.');

        return self::SUCCESS;
    }

    private function afficher(Owner $owner): void
    {
        $pose = $owner->access_key_set_at
            ? $owner->access_key_set_at->diffForHumans()
            : 'date inconnue (clé antérieure au suivi)';

        $this->newLine();
        $this->line("  <options=bold>{$owner->name}</> — {$owner->phone}".($owner->city ? " · {$owner->city}" : ''));
        $this->line("  Logements : {$owner->listings->count()}");
        $this->line("  Clé actuelle posée : {$pose}");
        $this->newLine();
    }
}
