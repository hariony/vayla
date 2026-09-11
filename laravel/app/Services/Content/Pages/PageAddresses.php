<?php

namespace App\Services\Content\Pages;

use App\Contracts\Repositories\PageRepositoryInterface;
use App\Exceptions\OfficeRefusal;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;

/**
 * L'adresse courte d'une page (`/comment-ca-marche`) : libre, et **jamais
 * celle d'un écran du site** — `/logements` ou `/connexion` resteraient ce
 * qu'ils sont, et la page serait introuvable.
 *
 * Les écrans sont lus **sur le routeur lui-même** : une liste recopiée
 * oublierait le prochain écran ajouté.
 */
final class PageAddresses
{
    /** Des adresses qui ne sont pas des écrans mais que le serveur réserve. */
    private const RESERVEES = ['api', 'up', 'images', 'build', 'storage', 'pages', 'favicon.ico', 'robots.txt', 'sitemap.xml', 'admin'];

    public function __construct(
        private PageRepositoryInterface $pages,
        private Router $routeur,
    ) {}

    /** @throws OfficeRefusal l'adresse est mal formée, ou déjà prise */
    public function verifier(string $slug, ?int $sauf = null): void
    {
        if ($slug === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new OfficeRefusal('L’adresse ne peut contenir que des lettres minuscules, des chiffres et des tirets.');
        }

        if (in_array($slug, self::RESERVEES, true) || $this->ecrans()->contains($slug)) {
            throw new OfficeRefusal("« /{$slug} » est déjà l’adresse d’un écran du site : choisissez-en une autre.");
        }

        if ($this->pages->adresseOccupee($slug, $sauf)) {
            throw new OfficeRefusal("Une autre page occupe déjà « /{$slug} ».");
        }
    }

    /** @return Collection<int, string> le premier segment de chaque route du site */
    private function ecrans(): Collection
    {
        return collect($this->routeur->getRoutes()->getRoutes())
            ->filter(fn (Route $r) => $r->getDomain() === null)
            ->map(fn (Route $r) => explode('/', trim($r->uri(), '/'))[0])
            ->filter(fn (string $s) => $s !== '' && ! str_starts_with($s, '{'))
            ->unique();
    }
}
