<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerAccountRequest;
use App\Http\Requests\OwnerPortraitRequest;
use App\Models\Owner;
use App\Services\OwnerPortraitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Le compte du propriétaire — ce qu'il peut corriger lui-même.
 *
 * **Rien de tout ça n'était modifiable.** Le nom, le numéro WhatsApp, la ville
 * et le compte mobile money étaient saisis à l'inscription puis figés : un
 * propriétaire qui changeait de numéro devait écrire à Vayla, et pendant ce
 * temps les demandes lui arrivaient sur une ligne qu'il n'avait plus.
 *
 * **L'adresse e-mail, elle, ne se change pas ici.** Elle est l'identifiant de
 * connexion depuis la disparition des mots de passe : la laisser modifiable
 * depuis une session ouverte reviendrait à offrir le compte à qui a emprunté
 * le téléphone. Elle se change en écrivant à Vayla, et l'écran le dit au lieu
 * d'afficher un champ grisé sans explication.
 */
class AccountController extends Controller
{
    public function __construct(
        private OwnerPortraitService $portraits,
    ) {}

    public function edit(Request $request): Response
    {
        $owner = $request->user('proprietaire');

        return Inertia::render('Owner/Account', [
            'compte' => [
                'name' => $owner->name,
                'phone' => $owner->phone,
                'email' => $owner->email,
                'city' => $owner->city,
                'address' => $owner->address,
                'portrait' => $owner->portrait,
                'mobileMoney' => $owner->mobile_money,
                'operator' => $owner->mobile_money_operator,
                // Le numéro est-il prouvé ? Il l'est par l'usage du lien
                // WhatsApp, jamais par sa simple saisie.
                'phoneVerifie' => $owner->phone_verified_at !== null,
            ],
            'operateurs' => OwnerAccountRequest::OPERATEURS,
        ]);
    }

    public function update(OwnerAccountRequest $request): RedirectResponse
    {
        /** @var Owner $owner */
        $owner = $request->user('proprietaire');
        $donnees = $request->validated();

        /*
         * **Changer de numéro annule la preuve qu'on le tenait.**
         * `phone_verified_at` n'enregistre pas une vérification maison : il
         * enregistre le fait que le lien d'accès envoyé sur ce WhatsApp a été
         * utilisé. Le numéro changé, cette preuve ne porte plus sur rien — la
         * garder ferait dire au compte une chose fausse, et c'est précisément
         * ce que Vayla reproche aux annonces qu'elle vérifie.
         */
        if ($donnees['phone'] !== $owner->phone) {
            $donnees['phone_verified_at'] = null;
        }

        $owner->fill($donnees)->save();

        return back()->with('succes', 'Votre compte est à jour.');
    }

    /**
     * **Le portrait est téléversé à part, pas avec le reste du formulaire.**
     * Un fichier de dix mégaoctets qui repart à chaque correction de numéro
     * serait une minute d'attente sur une connexion malgache, et un
     * enregistrement perdu quand elle coupe.
     */
    public function portrait(OwnerPortraitRequest $request): RedirectResponse
    {
        try {
            $this->portraits->poser($request->user('proprietaire'), $request->file('portrait'));
        } catch (RuntimeException $e) {
            // Un refus explicite — « il faut au moins 200 pixels » — plutôt
            // qu'un « fichier invalide » qui laisse chercher.
            return back()->withErrors(['portrait' => $e->getMessage()]);
        }

        return back()->with('succes', 'Votre photo est en place.');
    }

    public function portraitDestroy(Request $request): RedirectResponse
    {
        $this->portraits->retirer($request->user('proprietaire'));

        return back()->with('succes', 'Votre photo est retirée.');
    }
}
