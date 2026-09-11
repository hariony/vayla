<?php

namespace App\Http\Controllers;

use App\Services\Content\PageService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Une page éditoriale du site, à son adresse courte : `/comment-ca-marche`,
 * `/mentions-legales`.
 *
 * **La route est la dernière de `web.php`**, et c'est ce qui la rend sûre : un
 * écran du site passe toujours avant elle. Une page en brouillon, ou une
 * adresse inconnue, répondent 404 — la vraie page d'erreur, pas une page vide.
 */
class PageController extends Controller
{
    public function show(string $page, PageService $pages): Response
    {
        $trouvee = $pages->publiee($page) ?? abort(404);

        return Inertia::render('Content/Show', ['page' => $pages->pourLeSite($trouvee)]);
    }
}
