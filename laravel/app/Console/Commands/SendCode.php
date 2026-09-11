<?php

namespace App\Console\Commands;

use App\Enums\VerificationKind;
use App\Exceptions\CodeSendingFailed;
use App\Exceptions\CodeThrottled;
use App\Services\Verification\VerificationCodeService;
use App\Support\Telephone;
use Illuminate\Console\Command;

/**
 * Envoie un code à usage unique, et le vérifie.
 *
 * Sert à **éprouver la chaîne avant de la brancher sur un écran** : le jour où
 * un propriétaire s'inscrit, il ne faut pas découvrir que le modèle WhatsApp
 * n'était pas approuvé ou que l'expéditeur SMS était refusé par l'opérateur.
 *
 *   php artisan vayla:otp 0348531120              # envoie
 *   php artisan vayla:otp 0348531120 --code=123456  # vérifie
 *
 * Le canal est celui de la configuration (`VAYLA_OTP_DRIVER`) : la commande
 * **dit lequel** avant d'envoyer, pour qu'on ne croie jamais avoir testé
 * WhatsApp alors qu'on écrivait dans un fichier de journal.
 */
class SendCode extends Command
{
    protected $signature = 'vayla:code {destination : une adresse e-mail ou un numéro}
                            {--code= : Vérifie ce code au lieu d’en envoyer un}';

    protected $description = 'Envoie ou vérifie un code à usage unique (e-mail ou téléphone)';

    public function handle(VerificationCodeService $service): int
    {
        $brut = $this->argument('destination');
        $kind = str_contains($brut, '@') ? VerificationKind::Email : VerificationKind::Phone;

        if ($kind === VerificationKind::Phone && ! Telephone::depuis($brut)) {
            $this->error('Ce numéro n’est pas utilisable. Exemple : 034 00 000 01.');

            return self::FAILURE;
        }

        if ($code = $this->option('code')) {
            if ($service->verifier($kind, $brut, $code)) {
                $this->info("Code correct : {$brut} est vérifié.");

                return self::SUCCESS;
            }

            $this->error('Code incorrect, expiré, ou trop d’essais.');

            return self::FAILURE;
        }

        try {
            $ligne = $service->demander($kind, $brut);
        } catch (CodeThrottled|CodeSendingFailed $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        // La concaténation lie plus fort que `&&` et que le ternaire : sans
        // les parenthèses, la ligne n'affichait que la parenthèse d'alerte.
        $muet = $ligne->channel === 'log'
            || ($ligne->channel === 'mail' && config('mail.default') === 'log');

        $this->line("Canal : <options=bold>{$ligne->channel}</>"
            .($muet ? ' <fg=yellow>(rien n’est envoyé — voir storage/logs)</>' : ''));

        $this->info("Code envoyé à {$ligne->destination}, valable "
            .config('vayla.otp.ttl_minutes').' minutes.');
        $this->line("Vérifier : php artisan vayla:code {$brut} --code=XXXXXX");

        // Le code n'est **jamais** affiché ici, même en développement : il est
        // dans le canal, et l'afficher enlèverait tout intérêt au test.
        return self::SUCCESS;
    }
}
