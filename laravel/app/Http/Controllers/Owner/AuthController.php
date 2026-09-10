<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\OwnerAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sortir de l'espace propriétaire.
 *
 * **Entrer se fait ailleurs**, par la porte unique de `Owner\RegisterController` :
 * une adresse, un code, et le code décide si le compte s'ouvre ou s'il existait
 * déjà. Il n'y a plus de connexion par mot de passe à tenir ici.
 *
 * **La déconnexion est un POST, jamais un lien.** Un GET destructeur se
 * déclenche au préchargement d'un navigateur ou d'un antivirus, et le
 * propriétaire se retrouve dehors sans avoir rien touché.
 */
class AuthController extends Controller
{
    public function __construct(
        private OwnerAuthService $auth,
    ) {}

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->deconnecter();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('owner.login')->with('succes', 'Vous êtes déconnecté.');
    }
}
