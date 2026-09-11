<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Http\Requests\Office\OfficeLoginRequest;
use App\Services\Office\OfficeAuthService;
use App\Services\Office\OfficeThrottled;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La connexion au back-office : une adresse et un mot de passe.
 *
 * **L'échec ne dit jamais laquelle des deux valeurs est fausse**, et l'erreur
 * est posée sur l'adresse *et* rend le mot de passe vide : on ressaisit les
 * deux, sans indice. Voir `OfficeAuthService` pour la limite d'essais et
 * l'égalisation des temps de réponse.
 */
class AuthController extends Controller
{
    public function __construct(
        private OfficeAuthService $auth,
    ) {}

    public function form(): Response
    {
        return Inertia::render('Office/Login');
    }

    public function login(OfficeLoginRequest $request): RedirectResponse
    {
        try {
            $admin = $this->auth->connecter(
                $request->validated('email'),
                $request->validated('password'),
                (string) $request->ip(),
            );
        } catch (OfficeThrottled $e) {
            return back()->withInput($request->only('email'))->withErrors(['email' => $e->getMessage()]);
        }

        if (! $admin) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Adresse ou mot de passe incorrect.']);
        }

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        return redirect()->intended(route('office.home'));
    }

    /** Un POST, jamais un lien : un GET destructeur se déclenche au préchargement. */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('office.login');
    }
}
