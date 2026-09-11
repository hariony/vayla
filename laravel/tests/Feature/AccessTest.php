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

    /**
     * **Aucun menu ne promet un écran qui n'existe pas.**
     *
     * C'est la règle qui a fait retirer « Demander un séjour » de la barre : il
     * pointait sur une section dont le bouton pointait sur lui-même. Un menu de
     * compte et une barre d'espace sont les premiers endroits où l'on ajoute
     * « Mon profil » ou « Paramètres » avant d'avoir l'écran derrière — le test
     * relit donc les destinations écrites dans les composants et **les demande
     * vraiment**.
     *
     * Le corollaire vaut aussi : les rubriques à venir n'ont pas de `href`.
     * Elles sont écrites en toutes lettres dans la barre, sous un intitulé
     * « Bientôt », et n'ouvrent rien — c'est exactement pour ça qu'elles ne
     * peuvent pas casser ce test.
     */
    public function test_aucun_menu_ne_promet_un_ecran_qui_n_existe_pas(): void
    {
        // Les rubriques des deux espaces vivent au même endroit : la barre
        // latérale, le menu du compte et le tiroir mobile les lisent tous les
        // trois. Le menu du compte avait divergé — il ignorait « Messages » et
        // « Mes informations » côté client — ce que deux listes garantissent
        // toujours de finir par faire.
        $sources = [
            'js/Support/espaces.js',
            'js/Components/AccountMenu.vue',
        ];

        $owner = Owner::query()->firstOrFail();
        $voyageur = User::create(['email' => 'menu@example.com']);
        $vus = 0;

        foreach ($sources as $fichier) {
            preg_match_all("/href: '([^']+)'/", file_get_contents(resource_path($fichier)), $trouves);

            foreach ($trouves[1] as $href) {
                $reponse = str_starts_with($href, '/proprietaire')
                    ? $this->actingAs($owner, 'proprietaire')->get($href)
                    : $this->actingAs($voyageur)->get($href);

                $this->assertNotSame(
                    404,
                    $reponse->getStatusCode(),
                    "{$fichier} mène sur {$href}, qui n'existe pas."
                );

                $vus++;
            }
        }

        $this->assertGreaterThan(5, $vus, 'Les menus ne portent presque aucune destination.');
    }

    /**
     * **Le menu du compte et la barre latérale lisent la même liste.**
     *
     * Elles étaient écrites deux fois, et avaient déjà divergé : le menu
     * ignorait « Messages » et « Mes informations » côté client, si bien que
     * la même personne voyait deux menus différents selon qu'elle cliquait sur
     * sa pastille ou qu'elle se trouvait dans son espace. Le test regarde donc
     * la **cause** — une rubrique écrite en dur dans une des deux surfaces —
     * et pas seulement le symptôme.
     */
    public function test_les_rubriques_ne_sont_ecrites_qu_une_fois(): void
    {
        $surfaces = [
            'js/Components/AccountMenu.vue',
            'js/Pages/Owner/Partials/OwnerShell.vue',
            'js/Pages/Auth/Bookings.vue',
            'js/Pages/Auth/Messages.vue',
            'js/Pages/Auth/Account.vue',
        ];

        foreach ($surfaces as $fichier) {
            $source = file_get_contents(resource_path($fichier));

            $this->assertStringContainsString(
                "from '@/Support/espaces.js'",
                $source,
                $fichier.' doit lire les rubriques partagées.'
            );

            preg_match_all("/href: '([^']+)'/", $source, $trouves);

            $this->assertEmpty(
                $trouves[1],
                $fichier.' écrit une rubrique en dur : c’est ainsi que les deux menus ont divergé.'
            );
        }
    }

    /**
     * **Chaque ligne du menu porte son pictogramme.** Le menu aligne une
     * colonne d'icônes à gauche des intitulés ; une rubrique ajoutée sans la
     * sienne y laisserait un trou — et un pictogramme inconnu retombe sur un
     * point, qui se lit comme une erreur.
     */
    public function test_chaque_ligne_du_menu_porte_son_pictogramme(): void
    {
        $espaces = file_get_contents(resource_path('js/Support/espaces.js'));
        $icones = file_get_contents(resource_path('js/Components/SpaceIcon.vue'));

        preg_match_all("/\{[^{}]*href: '[^']+'[^{}]*\}/s", $espaces, $lignes);
        $this->assertNotEmpty($lignes[0]);

        foreach ($lignes[0] as $ligne) {
            $this->assertMatchesRegularExpression("/icone: '([a-z]+)'/", $ligne, "Ligne sans pictogramme : {$ligne}");

            preg_match("/icone: '([a-z]+)'/", $ligne, $icone);
            $this->assertStringContainsString("    {$icone[1]}: '", $icones, "Pictogramme « {$icone[1]} » introuvable.");
        }

        // Et la sortie a le sien.
        $this->assertStringContainsString("    sortir: '", $icones);
        $this->assertStringContainsString('<SpaceIcon name="sortir"', file_get_contents(resource_path('js/Components/AccountMenu.vue')));
    }

    /**
     * **Les deux espaces montent le même cadre.**
     *
     * C'est la leçon d'`AccessShell`, appliquée un cran plus loin : deux barres
     * latérales recopiées auraient divergé au premier ajustement, et l'une
     * aurait fini par ne plus dire ce que l'autre dit.
     */
    public function test_les_deux_espaces_montent_le_meme_cadre(): void
    {
        foreach (['js/Pages/Owner/Partials/OwnerShell.vue', 'js/Pages/Auth/Bookings.vue'] as $fichier) {
            $this->assertStringContainsString(
                '<SpaceShell',
                file_get_contents(resource_path($fichier)),
                $fichier.' doit monter le cadre commun des espaces.'
            );
        }
    }

    /**
     * **Les titres des espaces ont deux tailles, écrites une fois.**
     *
     * Le titre d'écran montait à 2,1 rem — 34 px au-dessus d'une barre
     * latérale en 15 : il écrasait ce qu'il introduisait. Et sept titres de
     * section recopiaient la même règle en 1,3 rem, trop près du titre d'écran
     * pour qu'on sache lequel introduisait l'autre. Le test tient les deux
     * bouts : la borne haute du titre, et aucun `<h2>` d'espace qui ne passe
     * par la primitive — c'est une huitième copie qui ferait rediverger.
     */
    public function test_les_titres_des_espaces_ont_deux_tailles_ecrites_une_fois(): void
    {
        $feuille = file_get_contents(resource_path('css/app.scss'));

        preg_match('/\n\.espace__titre \{(.*?)\}/s', $feuille, $titre);
        $this->assertNotEmpty($titre);
        $this->assertStringContainsString('1.6rem)', $titre[1], 'Le titre d’écran plafonne à 1,6 rem.');

        $ecrans = glob(resource_path('js/Pages/Owner').'/{,*/,Partials/,Listings/,Bookings/}*.vue', GLOB_BRACE)
            + glob(resource_path('js/Pages/Auth').'/{Account,Bookings,Messages}.vue', GLOB_BRACE);

        // Le nom d'un logement dans sa carte n'est pas un titre de section.
        $horsCategorie = ['ml__name'];

        foreach (array_unique($ecrans) as $fichier) {
            preg_match_all('/<h2 class="([^"]*)"/', file_get_contents($fichier), $h2);

            foreach ($h2[1] as $classes) {
                if (array_intersect(explode(' ', $classes), $horsCategorie)) {
                    continue;
                }

                $this->assertStringContainsString(
                    'espace__section',
                    $classes,
                    basename($fichier)." : un titre de section doit passer par .espace__section (« {$classes} »)."
                );
            }
        }
    }

    /**
     * **Aucun écran d'espace ne salue par le nom.**
     *
     * « Bonjour X » prenait le premier mot du nom — c'est-à-dire le **nom de
     * famille** dès qu'il est écrit à la malgache, « RAKOTOBE Hariony » — et
     * le criait en capitales à quelqu'un qu'on voulait accueillir. Le nom du
     * compte est de toute façon dans le menu de l'en-tête et au pied de la
     * colonne. Un écran de travail **se nomme**, il ne salue pas.
     */
    public function test_aucun_ecran_d_espace_ne_salue_par_le_nom(): void
    {
        $ecrans = glob(resource_path('js/Pages/Owner').'/{,*/}*.vue', GLOB_BRACE)
            + glob(resource_path('js/Pages/Auth').'/*.vue');

        foreach ($ecrans as $fichier) {
            $source = file_get_contents($fichier);

            preg_match('/<template>(.*)<\/template>/s', $source, $balisage);

            if (empty($balisage)) {
                continue;
            }

            // Les commentaires sont retirés : la première version de ce test
            // se déclenchait sur le commentaire qui explique justement la
            // règle. Un test qui échoue parce qu'on a documenté ce qu'il
            // vérifie n'apprend rien.
            $vu = preg_replace('/<!--.*?-->/s', '', $balisage[1]);

            $this->assertStringNotContainsString(
                'Bonjour',
                $vu,
                basename($fichier).' salue par le nom : le premier mot est le patronyme.'
            );
        }
    }

    /**
     * **La sortie est au pied de la colonne, et dans le menu de l'en-tête.**
     *
     * Ce n'est pas une redondance : dans un espace où l'on reste, la colonne
     * est ce qu'on parcourt, et c'est là qu'on cherche à sortir. Le menu de
     * l'en-tête sert partout ailleurs sur le site, où il n'y a pas de colonne.
     */
    public function test_la_sortie_est_au_pied_de_la_colonne(): void
    {
        $cadre = file_get_contents(resource_path('js/Components/SpaceShell.vue'));

        preg_match('/<template>(.*)<\/template>/s', $cadre, $balisage);

        $this->assertNotEmpty($balisage);
        $this->assertStringContainsString('class="esp__sortir"', $balisage[1]);
        $this->assertStringContainsString('Se déconnecter', $balisage[1]);

        // Un POST, jamais un lien : un GET destructeur part au préchargement.
        $this->assertStringContainsString('router.post(compte.value.sortie)', $cadre);
    }

    /**
     * **Un message de retour n'est pas une dalle.** C'était un aplat d'encre
     * plein, la chose la plus sombre de l'écran au-dessus d'un formulaire
     * blanc : il prenait l'œil comme une alerte alors qu'il dit « c'est fait ».
     * La carte est blanche ; seule sa petite marque ronde reste à l'encre.
     */
    /**
     * **La coche est visible au repos ; l'animation ne fait qu'y arriver.**
     * Posée invisible et révélée par l'animation, elle laissait un disque vide
     * partout où l'animation ne se jouait pas. Et l'espace entre les deux
     * phrases vit dans l'interpolation : en tête d'un nœud de texte, le
     * compilateur le condensait — « Compte créé.Décrivez ».
     */
    public function test_la_coche_du_message_se_voit_sans_animation(): void
    {
        $cadre = file_get_contents(resource_path('js/Components/SpaceShell.vue'));

        preg_match('/\n\.esp__flash-coche \{(.*?)\}/s', $cadre, $regle);

        $this->assertNotEmpty($regle);
        $this->assertStringContainsString('stroke-dashoffset: 0', $regle[1]);

        $this->assertStringContainsString("return reste ? ` \${reste}` : ''", $cadre);
    }

    public function test_le_message_de_succes_est_une_carte_claire(): void
    {
        $cadre = file_get_contents(resource_path('js/Components/SpaceShell.vue'));

        preg_match('/\n\.esp__flash \{(.*?)\}/s', $cadre, $regle);

        $this->assertNotEmpty($regle, 'Le message de retour doit avoir sa règle.');
        $this->assertStringContainsString('background: var(--white)', $regle[1]);
        $this->assertDoesNotMatchRegularExpression(
            '/\.esp__flash--ok \{[^}]*background: var\(--ink\)/',
            $cadre,
            'Le succès ne doit plus être un aplat d’encre.'
        );
    }

    /**
     * **Les deux espaces portent le même en-tête, celui du site.**
     *
     * L'espace propriétaire n'en avait pas, pour une raison précise : la barre
     * **recrutait**, et « Devenir hôte » n'a aucun sens pour quelqu'un qui
     * l'est déjà. Cette raison a disparu — l'en-tête suit la session, le
     * bouton se retire pour un propriétaire, et « Connexion » est devenu le
     * menu de son compte. Deux barres différentes pour deux espaces du même
     * produit obligeaient à réapprendre où sont les choses en changeant de
     * casquette.
     *
     * **Et `:search="false"` partout** : la forme compacte de l'en-tête efface
     * la navigation pour y encastrer le moteur de recherche. Sans moteur, elle
     * laisse une barre vide au premier défilement.
     */
    public function test_les_deux_espaces_portent_le_meme_en_tete(): void
    {
        $ecrans = [
            'js/Pages/Owner/Partials/OwnerShell.vue',
            'js/Pages/Auth/Bookings.vue',
            'js/Pages/Auth/Messages.vue',
            'js/Pages/Auth/Account.vue',
        ];

        foreach ($ecrans as $fichier) {
            $this->assertStringContainsString(
                '<SiteHeader :search="false" />',
                file_get_contents(resource_path($fichier)),
                $fichier.' doit monter l’en-tête du site, sans moteur de recherche.'
            );
        }

        // Et le cadre n'en porte plus aucun à lui : c'est ce qui garantit
        // qu'il n'y en aura pas deux.
        $cadre = file_get_contents(resource_path('js/Components/SpaceShell.vue'));

        preg_match('/<template>(.*)<\/template>/s', $cadre, $balisage);

        $this->assertNotEmpty($balisage);
        $this->assertStringNotContainsString('<header', $balisage[1]);
    }

    /**
     * **L'espace client n'a plus sa propre sortie.**
     *
     * Se déconnecter vit dans le menu du compte, en haut à droite, sur toutes
     * les pages du site. Le laisser aussi sur cet écran faisait deux sorties à
     * quinze centimètres d'écart, et donnait à croire que celle-ci était
     * particulière.
     */
    public function test_l_espace_client_n_a_plus_son_propre_bouton_de_sortie(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Auth/Bookings.vue'));

        // L'assertion regarde le **balisage**, pas le fichier : sa première
        // version se déclenchait sur le commentaire qui explique justement la
        // règle. Un test qui échoue parce qu'on a documenté ce qu'il vérifie
        // n'apprend rien.
        preg_match('/<template>(.*)<\/template>/s', $source, $balisage);

        $this->assertNotEmpty($balisage);
        $this->assertStringNotContainsString('Se déconnecter', $balisage[1]);
        $this->assertStringNotContainsString("router.post('/deconnexion')", $source);
    }

    /**
     * **Les deux déconnexions sont des POST, jamais des liens.**
     *
     * Un GET destructeur se déclenche au préchargement d'un navigateur ou d'un
     * antivirus : on se retrouve dehors sans avoir rien touché. Le test tient
     * les deux bouts — le composant ne pose pas de lien, et les routes elles-
     * mêmes refusent le GET.
     */
    public function test_se_deconnecter_ne_se_fait_jamais_par_un_lien(): void
    {
        $source = file_get_contents(resource_path('js/Components/AccountMenu.vue'));

        preg_match('/<template>(.*)<\/template>/s', $source, $balisage);

        $this->assertNotEmpty($balisage);
        $this->assertStringNotContainsString('/deconnexion"', $balisage[1]);
        $this->assertStringContainsString('router.post(url)', $source);

        $this->assertSame(405, $this->get('/deconnexion')->getStatusCode());
        $this->assertSame(405, $this->get('/proprietaire/deconnexion')->getStatusCode());
    }

    /**
     * **Connecté, l'en-tête montre le compte — pas un lien de plus.**
     *
     * Se déconnecter n'existait que *dans* `/mes-reservations` et *dans*
     * l'espace propriétaire : depuis l'accueil, une fiche ou le catalogue, un
     * voyageur connecté n'avait aucune sortie. Sur un téléphone partagé, c'est
     * la session de quelqu'un d'autre qu'on prend pour la sienne.
     */
    public function test_l_en_tete_montre_le_compte_quand_la_session_est_ouverte(): void
    {
        $source = file_get_contents(resource_path('js/Components/SiteHeader.vue'));

        preg_match('/<template>(.*)<\/template>/s', $source, $balisage);

        $this->assertNotEmpty($balisage);
        $this->assertStringContainsString('<AccountMenu v-if="connecte"', $balisage[1]);
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
