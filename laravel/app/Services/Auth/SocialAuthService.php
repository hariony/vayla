<?php

namespace App\Services\Auth;

use App\Enums\EspaceSocial;
use App\Enums\SocialProvider;
use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Rattacher une identité sociale à un compte Vayla.
 *
 * **Toute la logique des quatre cas vit ici, une seule fois pour les trois
 * fournisseurs.** Écrite dans le contrôleur, elle aurait été recopiée par
 * fournisseur, et l'une des copies aurait fini par rattacher ce que les autres
 * refusent — c'est-à-dire par ouvrir un compte à quelqu'un d'autre.
 *
 * Les quatre cas, dans l'ordre où on les rencontre :
 *
 * 1. **L'identité est déjà connue** → on connecte, et on rafraîchit ce qui
 *    n'engage rien (nom, avatar). Jamais l'adresse du compte : elle est notre
 *    identifiant, et un fournisseur n'a pas à la déplacer.
 * 2. **Une adresse *garantie* désigne un compte existant** → on rattache. La
 *    garantie est celle du fournisseur (`email_verified`), pas la nôtre :
 *    voir `SocialProvider::garantitLAdresse()`.
 * 3. **Une adresse *non garantie* désigne un compte existant** → on ne
 *    rattache **rien**. C'est le seul cas dangereux du dispositif : sans cette
 *    barrière, ouvrir un compte Facebook avec l'adresse d'un tiers suffirait à
 *    entrer chez lui. Le service refuse, et l'écran renvoie vers l'entrée par
 *    code — qui, elle, prouve la boîte.
 * 4. **Rien ne correspond** → on crée le compte et l'identité, puis on
 *    connecte.
 *
 * **Tout se fait dans une transaction.** Un compte créé sans son identité
 * sociale serait un compte fantôme : sans adresse — cas possible — plus
 * personne ne pourrait jamais y entrer.
 */
class SocialAuthService
{
    /**
     * `compte` vaut `null` dans un seul cas : un **propriétaire inconnu**. Son
     * compte exige un numéro joignable, que le fournisseur ne donne pas ; il
     * passe par la fiche, qui crée tout d'un coup.
     *
     * @return array{compte: Model|null, nouveau: bool}
     *
     * @throws LiaisonRefusee quand l'adresse n'est pas garantie par le fournisseur
     */
    public function rattacher(SocialProvider $provider, SocialiteUser $identite, EspaceSocial $espace): array
    {
        $id = (string) $identite->getId();
        $email = $this->adresse($identite);
        $brut = $identite->user ?? [];
        $garantie = $email !== null && $provider->garantitLAdresse(is_array($brut) ? $brut : []);

        $modele = $espace->modele();

        return DB::transaction(function () use ($provider, $identite, $id, $email, $garantie, $modele, $espace) {
            // ── 1. L'identité est déjà connue **dans cet espace**. Le type est
            // dans la clé : la même personne peut être voyageuse et
            // propriétaire avec le même compte Google, et ce sont bien deux
            // identités distinctes.
            $lien = SocialAccount::query()
                ->where('compte_type', $modele)
                ->where('provider', $provider->value)
                ->where('provider_user_id', $id)
                ->first();

            if ($lien) {
                $this->rafraichir($lien, $identite, $email, $garantie);

                return ['compte' => $lien->compte, 'nouveau' => false];
            }

            // ── 2 et 3. Une adresse désigne peut-être un compte existant.
            $existant = $email ? $modele::query()->where('email', $email)->first() : null;

            if ($existant && ! $garantie) {
                throw new LiaisonRefusee($provider, $email);
            }

            // **Un propriétaire ne se crée pas ici.** `owners.phone` est
            // obligatoire — c'est par là que Vayla appelle pour la
            // vérification, et une annonce sans numéro joignable ne dépasse
            // jamais le niveau 1. On rend donc la main : le contrôleur envoie
            // sur la fiche, qui crée le compte **et** le lien, exactement comme
            // pour celui qui arrive par un code.
            if (! $existant && $espace === EspaceSocial::Proprietaire) {
                return ['compte' => null, 'nouveau' => true];
            }

            $compte = $existant ?? $this->creer($modele, $identite, $email, $garantie);

            $compte->socialAccounts()->create([
                'provider' => $provider->value,
                'provider_user_id' => $id,
                'email' => $email,
                'name' => $identite->getName(),
                'avatar_url' => $identite->getAvatar(),
                'email_verified' => $garantie,
            ]);

            return ['compte' => $compte, 'nouveau' => $existant === null];
        });
    }

    /**
     * Lier une identité à un compte **déjà créé**.
     *
     * Sert au propriétaire, dont le compte naît sur la fiche et non au retour
     * du fournisseur. Le lien est posé là, une fois le numéro connu.
     */
    public function lier(Model $compte, SocialProvider $provider, array $identite): void
    {
        $compte->socialAccounts()->firstOrCreate(
            ['provider' => $provider->value, 'provider_user_id' => $identite['id']],
            [
                'email' => $identite['email'] ?? null,
                'name' => $identite['name'] ?? null,
                'avatar_url' => $identite['avatar'] ?? null,
                'email_verified' => (bool) ($identite['verifie'] ?? false),
            ]
        );
    }

    /**
     * Ce qu'on accepte de mettre à jour à chaque connexion : rien de critique.
     *
     * **Le nom d'Apple n'arrive qu'une fois.** Il n'est transmis qu'à la
     * toute première autorisation ; ensuite il est absent. On ne l'écrase donc
     * jamais avec du vide — sinon la deuxième connexion effacerait ce que la
     * première avait appris.
     */
    private function rafraichir(SocialAccount $lien, SocialiteUser $identite, ?string $email, bool $garantie): void
    {
        $lien->fill(array_filter([
            'name' => $identite->getName(),
            'avatar_url' => $identite->getAvatar(),
            'email' => $email,
        ]) + ['email_verified' => $garantie])->save();

        // Le compte Vayla ne prend un nom que s'il n'en avait pas : il est
        // demandé plus tard, et l'utilisateur a pu le corriger.
        if (! $lien->compte->name && $identite->getName()) {
            $lien->compte->forceFill(['name' => $identite->getName()])->save();
        }
    }

    private function creer(string $modele, SocialiteUser $identite, ?string $email, bool $garantie): Model
    {
        $compte = $modele::create([
            'name' => $identite->getName(),
            'email' => $email,
        ]);

        // L'adresse n'est marquée vérifiée que si le fournisseur l'atteste.
        // Sans ça, on lui prêterait notre propre preuve — celle du code.
        if ($email && $garantie) {
            $compte->forceFill(['email_verified_at' => Carbon::now()])->save();
        }

        return $compte;
    }

    /** Les adresses vivent en minuscules : une boîte, un compte. */
    private function adresse(SocialiteUser $identite): ?string
    {
        $email = $identite->getEmail();

        return $email ? mb_strtolower(trim($email)) : null;
    }
}
