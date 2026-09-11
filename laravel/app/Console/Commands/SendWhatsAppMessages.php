<?php

namespace App\Console\Commands;

use App\Enums\NotificationKind;
use App\Models\OutboundMessage;
use App\Contracts\Repositories\OutboundMessageRepositoryInterface;
use App\Support\Telephone;
use Illuminate\Console\Command;

/**
 * La file des messages WhatsApp à envoyer, avec leur lien prêt à cliquer.
 *
 * **Pourquoi c'est manuel, et pourquoi ce n'est pas un pis-aller.** L'API
 * WhatsApp Business de Meta exige une entreprise enregistrée, que Vayla n'a
 * pas encore. Un lien `wa.me` ouvre WhatsApp avec le message déjà écrit : un
 * clic, un appui sur envoyer, et le message part du numéro de Vayla. Ça ne
 * coûte rien, ça marche aujourd'hui, et à quelques demandes par jour c'est
 * tenable.
 *
 *   php artisan vayla:whatsapp              # ce qu'il reste à envoyer
 *   php artisan vayla:whatsapp --envoye=12  # marquer comme parti
 *   php artisan vayla:whatsapp --tout       # y compris ce qui est déjà parti
 *
 * Le jour où l'API arrive, cette commande devient un `dispatch` : les textes,
 * la file et les règles de non-répétition ne bougent pas.
 */
class SendWhatsAppMessages extends Command
{
    protected $signature = 'vayla:whatsapp
                            {--envoye= : Marque ce message comme envoyé}
                            {--test= : Met un message de test en file, vers ce numéro}
                            {--tout : Affiche aussi les messages déjà envoyés}';

    protected $description = 'Liste les messages WhatsApp à envoyer, avec leur lien prêt à cliquer';

    public function handle(OutboundMessageRepositoryInterface $file): int
    {
        if ($id = $this->option('envoye')) {
            return $this->marquer($file, (int) $id);
        }

        if ($numero = $this->option('test')) {
            return $this->test($file, $numero);
        }

        $messages = $this->option('tout')
            ? OutboundMessage::query()->with(['owner', 'booking'])->latest('id')->limit(50)->get()
            : $file->enAttente();

        if ($messages->isEmpty()) {
            $this->info('Rien à envoyer.');

            return self::SUCCESS;
        }

        foreach ($messages as $message) {
            $this->afficher($message);
        }

        $this->newLine();
        $this->comment($messages->count().' message(s). Cliquez le lien, envoyez, puis :');
        $this->line('  php artisan vayla:whatsapp --envoye=<id>');

        return self::SUCCESS;
    }

    /**
     * Un message de test vers un numéro quelconque.
     *
     * Sert à **vérifier le canal avant d'y mettre un vrai propriétaire** : le
     * jour où on envoie son premier lien d'accès, il ne faut pas découvrir que
     * le numéro était mal formé ou que WhatsApp n'y arrive pas.
     *
     * Le texte ne porte **aucun lien vers l'application** : sur un test, une
     * adresse `localhost` ne mène nulle part et ferait douter du reste du
     * message.
     */
    private function test(OutboundMessageRepositoryInterface $file, string $numero): int
    {
        $telephone = Telephone::depuis($numero);

        if (! $telephone) {
            $this->error("« {$numero} » n'est pas un numéro utilisable. Exemple : 034 00 000 01.");

            return self::FAILURE;
        }

        if (! $telephone->estMobile()) {
            $this->error('Ce numéro est une ligne fixe : WhatsApp n’y arrive pas.');

            return self::FAILURE;
        }

        $message = $file->enfiler(
            NotificationKind::Test,
            $telephone->e164(),
            implode("\n", [
                'Test Vayla',
                '',
                'Ceci est un message de test du canal de notification.',
                '',
                'Si vous lisez ceci, le canal fonctionne : les demandes de séjour, '
                    .'les rappels avant expiration et les messages des voyageurs partiront par ici.',
                '',
                'Envoyé le '.now()->translatedFormat('j F Y à H\hi').'.',
            ]),
            null,
            null,
        );

        $this->newLine();
        $this->info('Message de test mis en file pour '.$telephone->lisible()
            .($telephone->operateur() ? ' ('.$telephone->operateur().')' : ''));
        $this->newLine();
        $this->afficher($message->fresh());

        return self::SUCCESS;
    }

    private function marquer(OutboundMessageRepositoryInterface $file, int $id): int
    {
        $message = OutboundMessage::find($id);

        if (! $message) {
            $this->error("Aucun message n°{$id}.");

            return self::FAILURE;
        }

        $file->marquerEnvoye($message);
        $this->info("Message n°{$id} marqué comme envoyé.");

        return self::SUCCESS;
    }

    private function afficher(OutboundMessage $message): void
    {
        $etat = $message->envoye()
            ? '<fg=gray>envoyé '.$message->sent_at->diffForHumans().'</>'
            : ($message->kind->urgent() ? '<fg=red;options=bold>À ENVOYER</>' : '<options=bold>à envoyer</>');

        $this->newLine();
        $this->line("<options=bold>#{$message->id}</> · {$message->kind->label()} · {$etat}");
        $this->line("Pour : {$message->owner?->name} — {$message->to}");
        $this->newLine();

        // Le corps est indenté pour se distinguer des consignes de la
        // commande : sans ça on ne sait plus ce qui part et ce qui explique.
        foreach (explode("\n", $message->body) as $ligne) {
            $this->line('    <fg=cyan>'.$ligne.'</>');
        }

        $this->newLine();
        $this->line('    '.$message->lienWhatsApp());
        $this->line(str_repeat('─', 72));
    }
}
