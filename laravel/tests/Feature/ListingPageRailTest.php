<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le rail de la fiche de logement.
 *
 * **Un repère de position n'a de valeur que s'il ne se trompe jamais.** Il
 * désigne des ancres réelles : une rubrique qui pointerait sur un `id` absent
 * ne ferait rien au clic, et rien n'est pire qu'un raccourci qui ne bouge pas —
 * on cesse d'essayer les autres. Le test relit donc les rubriques écrites dans
 * la page et vérifie que chaque `id` existe **dans le balisage de cette même
 * page**.
 *
 * **Et l'ordre du rail est l'ordre de la page.** Un repère qui suivrait un
 * autre ordre désignerait la mauvaise ligne dès qu'on descend — c'est la seule
 * façon dont un rail juste devient un rail qui ment.
 */
class ListingPageRailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_le_rail_pointe_sur_des_ancres_qui_existent_et_dans_l_ordre(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Listings/Show.vue'));

        preg_match("/const RUBRIQUES = \[(.*?)\]/s", $source, $bloc);
        $this->assertNotEmpty($bloc, 'La fiche doit déclarer ses rubriques.');

        preg_match_all("/id: '([^']+)'/", $bloc[1], $rubriques);
        $ids = $rubriques[1];

        $this->assertGreaterThanOrEqual(5, count($ids), 'Un rail de deux entrées n’est pas un raccourci.');

        preg_match('/<template>(.*)<\/template>/s', $source, $balisage);
        $this->assertNotEmpty($balisage);

        $positions = [];

        foreach ($ids as $id) {
            $position = strpos($balisage[1], 'id="'.$id.'"');

            $this->assertNotFalse($position, "Le rail pointe sur #{$id}, qui n’existe pas dans la fiche.");

            $positions[] = $position;
        }

        $ordonnees = $positions;
        sort($ordonnees);

        $this->assertSame($ordonnees, $positions, 'Le rail doit suivre l’ordre de la page.');
    }

    /**
     * **Les deux surfaces lisent le même état.**
     *
     * Le rail de la colonne et les boutons posés sur la photographie affichent
     * la même chose ; chacun appelant le composable aurait posé deux jeux de
     * `ScrollTrigger` sur les mêmes sections, sans garantie qu'ils désignent la
     * même rubrique. C'est la leçon du moteur de recherche, qui existait en
     * deux exemplaires et pouvait afficher deux recherches différentes.
     */
    public function test_les_deux_ancrages_lisent_le_meme_etat(): void
    {
        $page = file_get_contents(resource_path('js/Pages/Listings/Show.vue'));

        // La page tient l'état une fois, et le passe aux deux ancrages : le
        // flottant, fixe au bord de la photographie, et celui posé dessus.
        $this->assertSame(1, substr_count($page, 'useSectionRail(RUBRIQUES)'));
        $this->assertSame(2, substr_count($page, ':pilote="rail"'));
        $this->assertStringContainsString('variante="photo"', $page);

        $this->assertStringNotContainsString(
            'useSectionRail(',
            file_get_contents(resource_path('js/Components/SectionDock.vue')),
            'SectionDock doit recevoir l’état, pas le créer.'
        );
    }

    /**
     * **Le raccourci suit le lecteur, et s'arrête au-dessus du pied de page.**
     *
     * Un raccourci qui remonte hors de l'écran avec la page n'est utile
     * qu'une fois. Mais en `position: fixed` il ignorait la page et finissait
     * posé sur le pied de page — une butée qu'un élément fixé à la fenêtre ne
     * peut pas connaître. Il colle donc (`sticky`) dans une bande qui a la
     * hauteur de la fiche, **dans le `<main>`** : la fiche finie, la bande
     * finie, et il remonte avec elle.
     */
    public function test_le_raccourci_suit_le_lecteur_sans_descendre_sur_le_pied_de_page(): void
    {
        $dock = file_get_contents(resource_path('js/Components/SectionDock.vue'));

        preg_match('/\.dock--flottant \{(.*?)\}/s', $dock, $regle);

        $this->assertNotEmpty($regle, 'La forme flottante doit avoir sa règle.');
        $this->assertStringContainsString('position: sticky', $regle[1]);
        $this->assertStringNotContainsString('position: fixed', $regle[1]);

        // La bande qui le porte vit dans le <main> de la fiche, pas à côté du
        // pied de page : c'est elle qui fait la butée.
        $page = file_get_contents(resource_path('js/Pages/Listings/Show.vue'));

        preg_match('/<main class="fiche">(.*?)<\/main>/s', $page, $main);

        $this->assertNotEmpty($main);
        $this->assertStringContainsString('<div class="fiche__couloir">', $main[1]);
        $this->assertStringContainsString('<SectionDock :sections="RUBRIQUES" :pilote="rail" />', $main[1]);
    }

    /**
     * **Un raccourci vers l'endroit où l'on se trouve déjà est un bouton qui
     * ne fait rien** — le pire des faux signaux. Les boutons sont posés sur la
     * photographie : la rubrique « Photos » n'y figure donc pas.
     */
    public function test_les_boutons_de_la_photo_ne_pointent_pas_sur_la_photo(): void
    {
        $this->assertStringContainsString(
            "filter((s) => s.id !== 'photos')",
            file_get_contents(resource_path('js/Components/SectionDock.vue'))
        );
    }

    /**
     * **Un seul des deux ancrages à la fois.** Deux jeux de raccourcis pour la
     * même chose sur un même écran feraient chercher ce qui les distingue.
     */
    public function test_un_seul_ancrage_s_affiche_a_la_fois(): void
    {
        $source = file_get_contents(resource_path('js/Components/SectionDock.vue'));

        $this->assertStringContainsString('@media (max-width: 1179px)', $source);
        $this->assertStringContainsString('@media (min-width: 1180px)', $source);
    }

    /**
     * **Les libellés sont écrits, toujours.** Une première version ne les
     * déployait qu'au survol — la faute des chevrons gris du calendrier sous
     * une forme plus jolie : au doigt il n'y a pas de survol, et un éclair ne
     * dit pas « Énergie » à qui découvre. Le test regarde les deux manières
     * dont on cache un mot sans le retirer : le masquer aux lecteurs d'écran,
     * ou le réduire à une largeur nulle.
     */
    public function test_les_libelles_des_raccourcis_sont_toujours_ecrits(): void
    {
        $source = file_get_contents(resource_path('js/Components/SectionDock.vue'));

        preg_match('/<span class="dock__legende"[^>]*>/', $source, $legende);

        $this->assertNotEmpty($legende, 'Chaque raccourci doit porter sa légende.');
        $this->assertStringNotContainsString('aria-hidden', $legende[0]);

        preg_match('/\.dock__legende \{(.*?)\}/s', $source, $regle);

        $this->assertNotEmpty($regle);
        $this->assertStringNotContainsString('max-width: 0', $regle[1]);
        $this->assertStringNotContainsString('opacity: 0', $regle[1]);
    }

    /**
     * **Toujours visible, jamais par-dessus le texte.** La fiche réserve un
     * couloir à gauche que le contenu n'occupe pas : laissés dans la seule
     * gouttière, les raccourcis se posaient sur la photographie dès 1440 px.
     */
    public function test_la_fiche_reserve_le_couloir_des_raccourcis(): void
    {
        $page = file_get_contents(resource_path('js/Pages/Listings/Show.vue'));

        $this->assertStringContainsString('--couloir', $page);
        $this->assertStringContainsString('padding-left: calc(var(--gutter) + var(--couloir))', $page);
    }

    /**
     * **Chaque rubrique a son pictogramme dessiné.** Une clé sans tracé
     * retomberait sur le point neutre : un raccourci sans image au milieu de
     * cinq qui en ont une se lit comme cassé.
     */
    public function test_chaque_rubrique_a_son_pictogramme(): void
    {
        $page = file_get_contents(resource_path('js/Pages/Listings/Show.vue'));
        $glyphes = file_get_contents(resource_path('js/Components/SectionGlyph.vue'));
        $gestes = file_get_contents(resource_path('js/Composables/useGlyphMotion.js'));

        preg_match("/const RUBRIQUES = \[(.*?)\]/s", $page, $bloc);
        preg_match_all("/icone: '([^']+)'/", $bloc[1], $icones);

        $this->assertNotEmpty($icones[1]);

        foreach ($icones[1] as $icone) {
            $this->assertStringContainsString("cle === '{$icone}'", $glyphes, "Pas de tracé pour « {$icone} ».");
            $this->assertMatchesRegularExpression("/^\s+{$icone}: \(tl/m", $gestes, "Pas de geste pour « {$icone} ».");
        }
    }

    /** La fiche s'ouvre toujours : le rail n'est qu'un raccourci par-dessus. */
    public function test_la_fiche_s_ouvre_avec_le_rail(): void
    {
        $this->get('/logements/villa-ambatoloaka')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Listings/Show'));
    }
}
