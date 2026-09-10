<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'aiguillage et l'espace client.
 *
 * **Choisir d'abord, se connecter ensuite.** `/connexion` ne porte que deux
 * cartes : arriver sur quatre champs et deux boutons sans avoir décidé où
 * l'on va, c'est commencer à taper dans le mauvais formulaire. Les
 * formulaires vivent sur `/connexion/client` et `/proprietaire/connexion`.
 *
 * Le test qui compte ici n'est pas la page, c'est **la forme des références** :
 * un jeu de démonstration qui s'écarte du format de production fait passer
 * pour cassé tout écran qui valide ce format. C'est exactement ce qui est
 * arrivé — le formulaire refusait les références de démo, qui portaient six
 * caractères là où le générateur en produit cinq.
 */
class AccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_l_aiguillage_s_ouvre_a_tous(): void
    {
        $this->get('/connexion')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Access/Index'));
    }

    /**
     * **L'aiguillage ne porte aucun formulaire.** C'est tout son objet : une
     * page qui ne demande qu'une chose — « qui êtes-vous ? » — se traverse
     * sans y penser, et ce qui suit est sans ambiguïté.
     */
    public function test_l_aiguillage_ne_porte_aucun_formulaire(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Access/Index.vue'));

        // On regarde le **balisage**, pas le fichier entier : la version
        // précédente cherchait « useForm » n'importe où et se déclenchait sur
        // le commentaire qui explique justement la règle. Un test qui échoue
        // parce qu'on a documenté ce qu'il vérifie n'apprend rien.
        preg_match('/<template>(.*)<\/template>/s', $source, $balisage);

        $this->assertNotEmpty($balisage, 'Le composant doit avoir un template.');
        $this->assertStringNotContainsString('<input', $balisage[1]);
        $this->assertStringNotContainsString('<form', $balisage[1]);
        $this->assertStringNotContainsString('useForm(', $source);

        // Mais il mène bien aux deux espaces.
        $this->assertStringContainsString('/connexion/client', $source);
        $this->assertStringContainsString('/proprietaire', $source);
    }

    /**
     * **Les écrans de connexion ne portent pas le chrome du site.** Une page
     * qui demande « qui êtes-vous ? » ou un mot de passe ne peut pas rouvrir
     * en même temps le catalogue, les destinations et « Devenir hôte » : ce
     * sont exactement les chemins qu'elle vient de refermer pour poser sa
     * question. Le pied de page ferait pire — il rouvre tout le site sous une
     * question qui n'attend qu'une réponse.
     *
     * Ce test tient les cinq écrans ensemble parce que c'est une règle de
     * famille : `Access/Index` était le dernier à porter l'en-tête, et rien
     * n'empêchait le prochain écran d'inscription de le reprendre.
     */
    public function test_les_ecrans_de_connexion_n_ont_ni_en_tete_ni_pied_de_page(): void
    {
        $ecrans = [
            'Access/Index.vue',
            'Access/Client.vue',
            'Auth/Register.vue',
            'Auth/Code.vue',
            'Owner/Register.vue',
        ];

        foreach ($ecrans as $ecran) {
            $source = file_get_contents(resource_path('js/Pages/'.$ecran));

            $this->assertStringNotContainsString('SiteHeader', $source, $ecran.' ne doit pas porter l\'en-tête du site.');
            $this->assertStringNotContainsString('SiteFooter', $source, $ecran.' ne doit pas porter le pied de page.');
        }
    }

    /**
     * **Mais aucun de ces écrans n'est un cul-de-sac.** Retirer le chrome sans
     * laisser de sortie enfermerait celui qui s'est trompé de porte : sur un
     * téléphone, la seule issue serait le bouton retour du navigateur, et une
     * partie de notre public ne le cherche pas. Le monogramme ramène à
     * l'accueil, et c'est la même convention sur toute la famille.
     *
     * **La sortie vit dans `AccessShell`, pas dans chaque page.** Sept écrans
     * la recopiaient, avec sept jeux de classes ; ils avaient déjà commencé à
     * diverger. Le test regarde donc que chaque écran monte bien le cadre —
     * s'en passer, c'est repartir sur une page qui n'a ni fond, ni monogramme,
     * ni sortie.
     */
    public function test_chaque_ecran_de_connexion_monte_le_cadre_commun(): void
    {
        $ecrans = [
            'Access/Index.vue',
            'Access/Client.vue',
            'Auth/Register.vue',
            'Auth/Code.vue',
            'Owner/Register.vue',
            'Owner/Login.vue',
        ];

        foreach ($ecrans as $ecran) {
            $source = file_get_contents(resource_path('js/Pages/'.$ecran));

            preg_match('/<template>(.*)<\/template>/s', $source, $balisage);

            $this->assertNotEmpty($balisage, $ecran.' doit avoir un template.');
            $this->assertStringContainsString('<AccessShell', $balisage[1], $ecran.' doit monter AccessShell.');
        }
    }

    /**
     * **Le cadre porte la sortie, et il la retire là où elle nuirait.**
     *
     * Deux écrans passent `:sortie="false"`, et les deux pour la même raison :
     * on n'y arrive pas, on y est *pendant* quelque chose. Sur `Auth/Code`
     * l'inscription vit en session — partir coûte tout ressaisir, et la sortie
     * est « Corriger ». Sur `Owner/Profile` l'adresse vient d'être vérifiée et
     * le compte n'existe pas encore : sortir perdrait la vérification.
     */
    public function test_seuls_les_ecrans_d_etape_n_ont_pas_de_sortie(): void
    {
        $sans = ['Auth/Code.vue', 'Owner/Profile.vue'];

        foreach ($sans as $ecran) {
            $this->assertStringContainsString(
                ':sortie="false"',
                file_get_contents(resource_path('js/Pages/'.$ecran)),
                $ecran.' est une étape : pas de sortie vers l\'accueil.'
            );
        }

        foreach (['Access/Index.vue', 'Access/Client.vue', 'Auth/Register.vue', 'Owner/Login.vue', 'Owner/Register.vue'] as $ecran) {
            $this->assertStringNotContainsString(
                ':sortie="false"',
                file_get_contents(resource_path('js/Pages/'.$ecran)),
                $ecran.' doit garder sa sortie vers l\'accueil.'
            );
        }

        // Et le cadre la rend bien : c'est lui qui porte la sortie.
        $cadre = file_get_contents(resource_path('js/Components/AccessShell.vue'));
        $this->assertMatchesRegularExpression('/<Link\s+v-if="sortie"\s+:href="retour"/', $cadre);
        $this->assertStringContainsString('VaylaMark', $cadre);
    }

    /**
     * **La sortie revient d'un pas, et ne promet plus l'accueil.**
     *
     * Elle y ramenait : depuis un écran atteint par l'aiguillage, c'était deux
     * pas en arrière au lieu d'un, et il fallait refaire le choix qu'on venait
     * de faire. Deux conséquences que ce test tient :
     *
     * 1. **Le mot n'est plus « vayla ».** Un monogramme qui ne ramène pas à
     *    l'accueil est un faux signal, et la convention est trop installée
     *    pour qu'on la retourne en silence.
     * 2. **Chaque écran déclare son parent, et ce n'est pas `history.back()`.**
     *    Cette version-là a été essayée et jetée : `history.length > 1` compte
     *    la page « nouvel onglet », si bien qu'un onglet ouvert sur un lien
     *    WhatsApp — le cas majoritaire chez nos propriétaires — sortait du
     *    site au premier clic. On ne peut pas lire les entrées d'historique
     *    pour savoir si la précédente est chez nous ; un parent déclaré, lui,
     *    est toujours juste, et ces écrans n'ont qu'un pas en amont.
     */
    public function test_la_sortie_revient_d_un_pas_et_ne_sort_jamais_du_site(): void
    {
        $cadre = file_get_contents(resource_path('js/Components/AccessShell.vue'));

        // On regarde **la sortie cliquable**, pas tout le balisage : la marque
        // inerte des écrans d'étape garde « vayla », et c'est juste — elle
        // situe la page, elle ne promet aucune action.
        preg_match('/<Link\s+v-if="sortie".*?<\/Link>/s', $cadre, $lien);

        $this->assertNotEmpty($lien, 'Le cadre doit porter une sortie cliquable.');
        $this->assertStringContainsString('Retour', $lien[0]);
        $this->assertStringNotContainsString('vayla', $lien[0], 'La sortie ne doit plus promettre l\'accueil.');

        // Et jamais par l'historique : il peut ramener hors du site. La règle
        // se vérifie sur le lien lui-même — c'est un lien nu, sans gestionnaire
        // de clic — et non sur le fichier, dont le commentaire explique
        // justement pourquoi `history.back()` a été écarté.
        $this->assertStringNotContainsString('@click', $lien[0]);
        $this->assertStringNotContainsString(':href="\'', $lien[0]);

        // Le parent est déclaré partout où la page n'est pas déjà le premier pas.
        $replis = [
            'Access/Client.vue' => '/connexion',
            'Auth/Register.vue' => '/connexion',
            'Owner/Login.vue' => '/connexion',
            'Owner/Register.vue' => '/connexion',
        ];

        foreach ($replis as $ecran => $attendu) {
            $this->assertStringContainsString(
                'retour="'.$attendu.'"',
                file_get_contents(resource_path('js/Pages/'.$ecran)),
                $ecran.' doit déclarer son pas en amont.'
            );
        }

        // L'aiguillage est le premier pas : son parent est l'accueil, par défaut.
        $this->assertStringNotContainsString('retour=', file_get_contents(resource_path('js/Pages/Access/Index.vue')));
    }

    /**
     * **Six cases dessinées, un seul champ réel.**
     *
     * Six `<input>` séparés — la solution qu'on écrit d'instinct — cassent le
     * collage du code, cassent le remplissage automatique `one-time-code`
     * (celui qui propose le code depuis la notification, sans changer
     * d'application), et obligent à déplacer le focus à la main, ce qui se
     * retourne toujours contre le clavier et les lecteurs d'écran. Le champ
     * est donc unique et transparent, posé par-dessus les cases.
     *
     * Le test vaut surtout pour la prochaine refonte : l'écran *ressemble* à
     * six champs, et c'est exactement ce qui donne envie d'en écrire six.
     */
    public function test_l_ecran_de_code_n_a_qu_un_seul_champ(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Auth/Code.vue'));

        preg_match('/<template>(.*)<\/template>/s', $source, $balisage);

        $this->assertNotEmpty($balisage);
        $this->assertSame(1, substr_count($balisage[1], '<input'), 'Le code se saisit dans un champ unique.');
        $this->assertStringContainsString('autocomplete="one-time-code"', $balisage[1]);
        $this->assertStringContainsString('inputmode="numeric"', $balisage[1]);
    }

    /**
     * **Connecté, « Connexion » devient la porte de son espace.**
     *
     * Le retirer purement et simplement laisserait quelqu'un de connecté sans
     * aucun chemin vers ses réservations depuis le site public : la même
     * impasse qu'avant, dans l'autre sens. Le garder tel quel est un faux
     * signal — un lien qui propose de se connecter à qui l'est déjà fait
     * douter d'avoir été déconnecté. Il change donc de libellé et de cible.
     *
     * « Devenir hôte » disparaît pour un propriétaire : ça n'a aucun sens pour
     * quelqu'un qui l'est déjà, et c'est la même raison qui vaut à l'intérieur
     * de son espace.
     */
    public function test_l_en_tete_suit_la_session(): void
    {
        // L'en-tête est rendu côté client : les libellés ne sont pas dans le
        // HTML servi. Ce que le serveur fournit, ce sont les gardes — c'est
        // donc là-dessus qu'on l'interroge.
        $this->get('/')->assertOk()->assertInertia(
            fn ($page) => $page->where('auth.user', null)->where('auth.owner', null)
        );

        $voyageur = User::create(['email' => 'connecte@example.com']);
        $this->actingAs($voyageur)->get('/')->assertOk()->assertInertia(
            fn ($page) => $page->where('auth.user.email', 'connecte@example.com')->where('auth.owner', null)
        );

        $this->post('/deconnexion');

        $owner = Owner::query()->firstOrFail();
        $this->actingAs($owner, 'proprietaire')->get('/')->assertOk()->assertInertia(
            fn ($page) => $page->where('auth.owner.name', $owner->name)
        );

        // Et l'en-tête s'en sert : le lien suit la session au lieu de pointer
        // en dur sur `/connexion`, et « Devenir hôte » se retire pour un
        // propriétaire.
        $source = file_get_contents(resource_path('js/Components/SiteHeader.vue'));

        preg_match('/<template>(.*)<\/template>/s', $source, $balisage);

        $this->assertNotEmpty($balisage);
        $this->assertStringContainsString(':href="espace.href"', $balisage[1]);
        $this->assertStringContainsString('{{ espace.label }}', $balisage[1]);
        $this->assertStringContainsString('v-if="recrute"', $balisage[1]);
        $this->assertStringNotContainsString('href="/connexion"', $balisage[1]);
    }

    public function test_l_espace_client_ne_porte_que_la_connexion(): void
    {
        $this->get('/connexion/client')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Access/Client'));
    }

    /**
     * **La règle que le formulaire a révélée.** `BookingService::reference()`
     * produit `VY-` plus cinq caractères, sans O/0 ni I/1 — un code qui se
     * dicte au téléphone. Toute référence en base doit avoir cette forme, y
     * compris celles du jeu de démonstration.
     */
    public function test_toutes_les_references_ont_la_forme_de_production(): void
    {
        foreach (Booking::all() as $booking) {
            $this->assertMatchesRegularExpression(
                '/^VY-[A-Z0-9]{5}$/',
                $booking->reference,
                "Référence hors format : {$booking->reference}"
            );

            // Ni O ni 0, ni I ni 1 : le code se dicte au téléphone et se
            // recopie sur WhatsApp.
            $this->assertDoesNotMatchRegularExpression(
                '/[O0I1L]/',
                substr($booking->reference, 3),
                "Caractère ambigu dans la référence : {$booking->reference}"
            );
        }
    }

    /**
     * **L'entrée par référence a quitté l'écran de connexion.**
     *
     * Elle y ouvrait une réservation, pas un compte : deux portes sur un
     * écran qui n'en demande qu'une, et la seconde faisait retaper un code
     * que le voyageur avait déjà sous les yeux dans son message. Le POST qui
     * la servait n'existe plus — un point d'entrée mort qui accepte encore
     * des requêtes est une surface d'énumération qu'on garde sans raison.
     *
     * `GET /reservations/{reference}` n'a pas bougé : c'est le lien du
     * message de confirmation, et `KeyAccessTest` tient sa limite de débit.
     */
    public function test_l_ouverture_par_reference_ne_passe_plus_par_la_connexion(): void
    {
        $reference = Booking::query()->value('reference');

        $this->post('/connexion/reservation', ['reference' => $reference])->assertNotFound();

        // Mais le lien du message de confirmation, lui, ouvre toujours.
        $this->get("/reservations/{$reference}")->assertOk();

        // Et l'écran ne demande plus de référence. On regarde le **balisage**
        // et non le fichier : le commentaire de tête explique justement
        // pourquoi la référence est partie, et un test qui échoue parce qu'on
        // a documenté ce qu'il vérifie n'apprend rien — l'erreur a déjà été
        // faite une fois ici.
        $source = file_get_contents(resource_path('js/Pages/Access/Client.vue'));
        preg_match('/<template>(.*)<\/template>/s', $source, $balisage);

        $this->assertNotEmpty($balisage);
        $this->assertStringNotContainsString('reference', $balisage[1]);
        $this->assertStringNotContainsString('VY-', $balisage[1]);
    }
}
