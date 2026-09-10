<?php

namespace App\Console\Commands;

use App\Support\Telephone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Diagnostic du canal WhatsApp Cloud API.
 *
 * **Il existe parce que les deux causes d'échec se ressemblent à l'écran.**
 * « Le message n'a pas pu partir » peut vouloir dire que le jeton est périmé,
 * que le modèle n'est pas approuvé, que le numéro n'est pas dans la liste des
 * destinataires de test, ou que le composant bouton ne correspond pas au
 * modèle. Meta répond précisément à chacune — et le service masque cette
 * réponse à l'utilisateur, à raison. Ici on la montre en entier.
 *
 *   php artisan vayla:whatsapp-check                  # état de la configuration
 *   php artisan vayla:whatsapp-check 0348531120       # envoie `hello_world`
 *
 * **`hello_world` d'abord, votre modèle ensuite.** Ce modèle est fourni par
 * Meta, ne prend aucun paramètre et existe sur tout compte neuf : s'il part,
 * le jeton, l'expéditeur et le destinataire sont bons, et ce qui reste à
 * régler est le modèle. Sans cette séparation, on corrige au hasard.
 */
class CheckWhatsApp extends Command
{
    protected $signature = 'vayla:whatsapp-check {numero? : Envoie `hello_world` à ce numéro}';

    protected $description = 'Vérifie la configuration WhatsApp Cloud API et envoie un message d’essai';

    private const VERSION = 'v21.0';

    public function handle(): int
    {
        $token = config('vayla.otp.whatsapp.token');
        $expediteur = config('vayla.otp.whatsapp.phone_number_id');

        $this->newLine();
        $this->ligne('Pilote OTP actif', config('vayla.otp.driver'), config('vayla.otp.driver') === 'whatsapp');
        $this->ligne('WHATSAPP_TOKEN', $token ? 'posé ('.strlen($token).' caractères)' : 'MANQUANT', (bool) $token);
        $this->ligne('WHATSAPP_PHONE_NUMBER_ID', $expediteur ?: 'MANQUANT', (bool) $expediteur);
        $this->ligne('Modèle', config('vayla.otp.whatsapp.template').' ('.config('vayla.otp.whatsapp.lang').')', true);
        $this->ligne('Bouton « copier »', config('vayla.otp.whatsapp.copy_button') ? 'envoyé' : 'omis', true);
        $this->newLine();

        if (! $token || ! $expediteur) {
            $this->error('Configuration incomplète : renseignez WHATSAPP_TOKEN et WHATSAPP_PHONE_NUMBER_ID,');
            $this->error('puis `docker compose up -d --force-recreate app` (le .env est figé à la création).');

            return self::FAILURE;
        }

        if (! $numero = $this->argument('numero')) {
            $this->comment('Ajoutez un numéro pour envoyer un message d’essai :');
            $this->line('  php artisan vayla:whatsapp-check 0348531120');

            return self::SUCCESS;
        }

        return $this->essai($token, $expediteur, $numero);
    }

    private function essai(string $token, string $expediteur, string $brut): int
    {
        $numero = Telephone::depuis($brut);

        if (! $numero) {
            $this->error('Ce numéro n’est pas utilisable. Exemple : 034 00 000 01.');

            return self::FAILURE;
        }

        $this->line('Envoi de <options=bold>hello_world</> à '.$numero->lisible().'…');
        $this->newLine();

        $reponse = Http::withToken($token)->timeout(20)->post(
            sprintf('https://graph.facebook.com/%s/%s/messages', self::VERSION, $expediteur),
            [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($numero->e164(), '+'),
                'type' => 'template',
                // `hello_world` est fourni par Meta et ne prend aucun
                // paramètre : s'il part, le problème est dans votre modèle,
                // pas dans vos identifiants.
                'template' => ['name' => 'hello_world', 'language' => ['code' => 'en_US']],
            ]
        );

        if ($reponse->successful()) {
            $this->info('Message accepté par Meta.');
            $this->line('  '.$reponse->body());
            $this->newLine();
            $this->comment('Si rien n’arrive sur le téléphone : avec un numéro de test, le destinataire');
            $this->comment('doit être déclaré dans « To » du tableau de bord Meta et avoir validé son code.');

            return self::SUCCESS;
        }

        $this->error('Meta a refusé le message ('.$reponse->status().') :');
        $this->newLine();
        $this->line($reponse->body());
        $this->newLine();
        $this->expliquer($reponse->json('error.code'));

        return self::FAILURE;
    }

    /** Les quatre refus qu'on rencontre réellement, et ce qu'ils veulent dire. */
    private function expliquer(?int $code): void
    {
        $pistes = match ($code) {
            190 => ['Le jeton est périmé ou révoqué.',
                'Un jeton temporaire du tableau de bord ne vit que 24 h : créez un jeton d’utilisateur système pour un usage durable.'],
            131030 => ['Le numéro n’est pas dans la liste des destinataires autorisés.',
                'Avec un numéro d’expéditeur de test, Meta n’envoie qu’aux numéros déclarés dans « To » et validés par code.'],
            132001 => ['Le modèle n’existe pas dans cette langue.',
                'Vérifiez le nom exact et le code de langue (fr, fr_FR, en_US…) dans le gestionnaire de modèles.'],
            132000 => ['Le nombre de paramètres ne correspond pas au modèle.',
                'C’est en général le bouton : WHATSAPP_OTP_COPY_BUTTON=false si votre modèle n’a pas de bouton de copie.'],
            default => null,
        };

        if (! $pistes) {
            return;
        }

        $this->warn($pistes[0]);
        $this->line('  → '.$pistes[1]);
    }

    private function ligne(string $cle, string $valeur, bool $ok): void
    {
        $this->line(sprintf('  %s %-28s %s',
            $ok ? '<fg=green>✓</>' : '<fg=red>✗</>',
            $cle,
            "<options=bold>{$valeur}</>"));
    }
}
