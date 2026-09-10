{{--
    Page 404 de Vayla.

    Blade et non Inertia : une page d'erreur doit s'afficher même si le
    paquet JavaScript ne se charge pas. Elle reprend les jetons du système de
    design en dur, pour la même raison — elle ne dépend d'aucune feuille
    construite par Vite.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Page introuvable · Vayla</title>
    <style>
        :root { --terre-500: #C9452A; --ink: #17141C; --text-2: #55505E; --text-3: #837D8E; --off-1: #FAF8F7; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem;
            background: var(--off-1);
            color: var(--ink);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            text-align: center;
        }
        main { max-width: 34rem; }
        svg { width: 46px; height: 46px; color: var(--ink); }
        h1 { margin: 1.6rem 0 0; font-size: clamp(1.7rem, 5vw, 2.4rem); font-weight: 800; letter-spacing: -.035em; }
        p { margin: .9rem 0 0; font-size: 1rem; line-height: 1.65; color: var(--text-2); }
        .note { margin-top: .5rem; font-size: .85rem; color: var(--text-3); }
        .actions { margin-top: 2rem; display: flex; flex-wrap: wrap; gap: .7rem; justify-content: center; }
        a {
            display: inline-flex; align-items: center;
            padding: .78rem 1.4rem;
            border-radius: 999px;
            font-size: .92rem; font-weight: 700; text-decoration: none;
            transition: transform .3s ease, box-shadow .3s ease;
        }
        a:hover { transform: translateY(-2px); }
        .primary { background: var(--terre-500); color: #fff; box-shadow: 0 10px 24px -12px rgba(201, 69, 42, .8); }
        .ghost { color: var(--ink); border: 1px solid rgba(23, 20, 28, .16); }
    </style>
</head>
<body>
    <main>
        {{-- Le monogramme : un V qui est aussi une coche. --}}
        <svg viewBox="0 0 48 48" fill="none" aria-hidden="true">
            <path d="M2.18,4.81 L19.79,45.70 Q38.12,20.37 45.82,2.30 L38.58,4.12 Q27.42,20.47 21.66,26.12 L9.01,7.47 Z" fill="currentColor" />
        </svg>

        <h1>Cette page n'existe pas</h1>

        <p>
            Le logement que vous cherchez n'est plus en ligne, ou l'adresse
            comporte une erreur.
        </p>

        @if (! empty($message))
            <p class="note">{{ $message }}</p>
        @endif

        <div class="actions">
            <a class="primary" href="/logements">Voir les logements</a>
            <a class="ghost" href="/">Retour à l'accueil</a>
        </div>
    </main>
</body>
</html>
