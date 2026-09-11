<?php

namespace App\Services\Verification;

use App\Contracts\Repositories\VerificationCodeRepositoryInterface;
use App\Contracts\Verification\CodeSender;
use App\Enums\VerificationKind;
use App\Exceptions\CodeSendingFailed;
use App\Exceptions\CodeThrottled;
use App\Models\VerificationCode;
use App\Support\Telephone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Le code à usage unique qui prouve une adresse ou un numéro.
 *
 * **Un OTP mal borné est pire que pas d'OTP.** Le code lui-même est trivial —
 * six chiffres, un hachage, une comparaison. Tout ce qui compte est autour, et
 * chacune de ces cinq règles répare une attaque réelle :
 *
 * 1. **Le code est stocké haché**, jamais en clair. Une base copiée ne doit
 *    pas livrer des codes vivants : six chiffres se rejouent en une seconde.
 * 2. **Cinq essais par code.** Un million de combinaisons, cinq tirages : le
 *    hasard est hors de portée, et personne n'est bloqué pour deux fautes de
 *    frappe.
 * 3. **Le compteur d'essais est sur le code, pas sur la destination.**
 *    L'inverse permettrait d'enfermer quelqu'un dehors en épuisant ses essais
 *    depuis l'extérieur.
 * 4. **Une minute avant de pouvoir en redemander un.** Sans ce délai, le
 *    bouton « renvoyer » devient un distributeur de messages.
 * 5. **Cinq codes par heure et par destination.** La seule borne qui protège à
 *    la fois le compte d'envoi de Vayla et la boîte de quelqu'un d'autre.
 *
 * **Ces cinq règles sont écrites ici et nulle part ailleurs.** Elles valent
 * pour un e-mail comme pour un téléphone : les recopier par canal aurait
 * garanti qu'une des deux versions finisse par mentir.
 *
 * **Un code demandé annule les précédents.** Deux codes vivants doublent les
 * chances d'un tirage au sort, et surtout celui qui en a reçu deux essaie le
 * mauvais et croit le service cassé.
 */
class VerificationCodeService
{
    /** @param  iterable<CodeSender>  $canaux */
    public function __construct(
        private iterable $canaux,
        private VerificationCodeRepositoryInterface $codes,
    ) {}

    /**
     * @throws CodeThrottled trop tôt, ou trop de codes dans l'heure
     * @throws CodeSendingFailed le canal n'a pas pu délivrer
     */
    public function demander(VerificationKind $kind, string $destination): VerificationCode
    {
        $cible = $this->normaliser($kind, $destination);
        $canal = $this->canalPour($kind);

        $this->verifierLesBornes($cible);

        $code = $this->tirer();

        $this->codes->expirerEnCours($cible);

        $ligne = $this->codes->creer($cible, $kind, Hash::make($code), $canal->canal(), Carbon::now()->addMinutes((int) config('vayla.otp.ttl_minutes')));

        try {
            $canal->envoyer($cible, $code);
        } catch (CodeSendingFailed $e) {
            // Un code qui n'est pas parti ne doit pas rester jouable : il
            // occuperait le quota horaire et bloquerait la vraie tentative
            // suivante.
            $this->codes->expirer($ligne);

            throw $e;
        }

        return $ligne;
    }

    /**
     * L'essai est compté **avant** la comparaison : sinon une requête
     * interrompue au bon moment donnerait des essais gratuits.
     */
    public function verifier(VerificationKind $kind, string $destination, string $saisie): bool
    {
        $cible = $this->normaliser($kind, $destination);
        $maximum = (int) config('vayla.otp.max_attempts');

        $ligne = $this->codes->dernierEnCours($cible);

        if (! $ligne || ! $ligne->vivant($maximum)) {
            return false;
        }

        $this->codes->compterEssai($ligne);

        // `Hash::check` compare en temps constant : une comparaison naïve
        // laisserait mesurer le nombre de caractères justes.
        if (! Hash::check(trim($saisie), $ligne->code_hash)) {
            return false;
        }

        $this->codes->marquerVerifie($ligne);

        return true;
    }

    /** Une destination prouvée dans l'heure : ce que le formulaire d'inscription lira. */
    public function estVerifiee(VerificationKind $kind, string $destination): bool
    {
        return $this->codes->verifieDepuis($this->normaliser($kind, $destination), Carbon::now()->subHour());
    }

    /** Secondes restantes avant de pouvoir en redemander un. Zéro = tout de suite. */
    public function attenteAvantRenvoi(VerificationKind $kind, string $destination): int
    {
        $dernier = $this->codes->dernier($this->normaliser($kind, $destination));

        if (! $dernier) {
            return 0;
        }

        return max(0, (int) config('vayla.otp.resend_seconds')
            - (int) $dernier->created_at->diffInSeconds(Carbon::now()));
    }

    /**
     * Une seule forme par destination.
     *
     * Sans ça, `Jean@Gmail.com` et `jean@gmail.com` seraient deux quotas
     * distincts — et un code vérifié sur l'un n'ouvrirait pas l'autre.
     */
    private function normaliser(VerificationKind $kind, string $destination): string
    {
        return $kind === VerificationKind::Email
            ? mb_strtolower(trim($destination))
            : (Telephone::depuis($destination)?->e164() ?? trim($destination));
    }

    private function canalPour(VerificationKind $kind): CodeSender
    {
        foreach ($this->canaux as $canal) {
            if ($canal->sert($kind)) {
                return $canal;
            }
        }

        throw new CodeSendingFailed("Aucun canal ne sait vérifier une {$kind->label()}.");
    }

    /** @throws CodeThrottled */
    private function verifierLesBornes(string $cible): void
    {
        $dernier = $this->codes->dernier($cible);

        if ($dernier) {
            $reste = max(0, (int) config('vayla.otp.resend_seconds')
                - (int) $dernier->created_at->diffInSeconds(Carbon::now()));

            if ($reste > 0) {
                throw new CodeThrottled(
                    "Un code vient d'être envoyé. Attendez {$reste} seconde".($reste > 1 ? 's' : '')
                    .' avant d’en demander un autre.',
                    $reste,
                );
            }
        }

        $parHeure = $this->codes->nombreDepuis($cible, Carbon::now()->subHour());

        if ($parHeure >= (int) config('vayla.otp.max_per_hour')) {
            throw new CodeThrottled(
                'Trop de codes demandés. Réessayez dans une heure, ou écrivez à Vayla.',
                3600,
            );
        }
    }

    /**
     * Six chiffres, tirés par le générateur cryptographique.
     *
     * `random_int` et non `rand` : ce dernier est prévisible à partir de
     * quelques tirages, ce qui rendrait le code devinable sans même essayer.
     */
    private function tirer(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
