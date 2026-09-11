<?php

namespace App\Services\Content\Pages;

use App\Contracts\Settings\SettingsStore;
use App\Data\Content\PageHeadingData;
use App\Data\Content\RenderedPageData;
use App\Support\Pourcent;
use Illuminate\Support\Str;

/**
 * Le Markdown d'une page en HTML sûr, avec les ancres des intertitres et le
 * sommaire. Le même moteur sert la page publiée et l'aperçu du back-office :
 * ce qu'on voit en écrivant est ce qui sortira.
 *
 * **Le HTML tapé est retiré, pas échappé ni exécuté** (`html_input: strip`),
 * comme les liens `javascript:` : un compte d'équipe compromis ne doit pas
 * pouvoir poser un script sur le site public.
 *
 * **Un chiffre qui existe ailleurs ne se recopie pas dans une page** :
 * `{commission}` s'écrit « 5 % » au moment de l'affichage, depuis le réglage.
 * Recopié à la main, le taux de la page « Tarifs » mentirait le jour où il
 * changerait.
 */
final class PageRenderer
{
    public function __construct(
        private SettingsStore $reglages,
    ) {}

    public function rendre(string $markdown): RenderedPageData
    {
        $markdown = str_replace('{commission}', Pourcent::de($this->reglages->commission()), $markdown);

        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);

        $sommaire = [];
        $html = $this->ancrer($html, $sommaire);

        // Un lien vers l'extérieur s'ouvre ailleurs, sans donner la main à la
        // page ouverte sur la nôtre.
        $html = preg_replace('/<a href="(https?:\/\/[^"]+)"/', '<a href="$1" target="_blank" rel="noopener noreferrer"', $html);

        return new RenderedPageData($html, $sommaire);
    }

    /**
     * Pose une ancre sur chaque intertitre, et le range au sommaire. Deux
     * intertitres identiques reçoivent deux ancres distinctes.
     *
     * @param  list<PageHeadingData>  $sommaire
     */
    private function ancrer(string $html, array &$sommaire): string
    {
        $vus = [];

        return preg_replace_callback('/<h2>(.*?)<\/h2>/s', function (array $m) use (&$sommaire, &$vus) {
            $label = trim(strip_tags($m[1]));
            $id = Str::slug($label) ?: 'section';
            $id = isset($vus[$id]) ? $id.'-'.(++$vus[$id]) : $id;
            $vus[$id] ??= 1;
            $sommaire[] = new PageHeadingData($id, html_entity_decode($label, ENT_QUOTES | ENT_HTML5));

            return "<h2 id=\"{$id}\">{$m[1]}</h2>";
        }, $html);
    }
}
