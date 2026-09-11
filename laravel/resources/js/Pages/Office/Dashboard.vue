<script setup>
/**
 * Le tableau de bord du back-office.
 *
 * **Ce qui attend quelqu'un, avant ce qui se mesure.** On n'ouvre pas cet
 * écran pour regarder des courbes : on l'ouvre parce qu'une annonce attend son
 * appel, qu'une demande expire ou qu'un message WhatsApp n'est pas parti. La
 * file vient donc en premier, en cinq tuiles qui se cliquent en entier ; une
 * tuile à zéro s'éteint au lieu de disparaître — « rien en attente » est une
 * information, et une file qui changerait de forme selon les jours ne se lirait
 * plus d'un coup d'œil.
 *
 * **Les chiffres ne sont que des comptes.** Pas de moyenne, pas de flèche de
 * tendance : sur un produit dont toute la promesse est la vérification, un
 * « +12 % » sans définition serait le premier chiffre invérifiable.
 *
 * **L'échelle du catalogue se lit par barreau**, jamais en moyenne — la règle
 * des pages destination. Le lagon y est chez lui : c'est une vérification.
 */
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { ariary, ilYA, nombre } from '@/Support/format.js'
import { photoSrc } from '@/Support/photo.js'
import { formatCompact } from '@/Composables/useStayDates.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    aTraiter: { type: Array, required: true },
    annonces: { type: Array, default: () => [] },
    demandes: { type: Array, default: () => [] },
    chiffres: { type: Object, required: true },
    echelle: { type: Array, default: () => [] },
    activite: { type: Array, default: () => [] },
})

const racine = ref(null)
useOfficeMotion(racine)

const aujourdhui = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date())

const plusHaut = Math.max(1, ...props.echelle.map((n) => n.nombre))

/** Une demande a 48 h ; la mèche dit ce qu'il en reste. */
const meche = (heures) => Math.max(.04, Math.min(1, (heures ?? 0) / 48))

const CHIFFRES = [
    { cle: 'enLigne', label: 'Annonces en ligne' },
    { cle: 'proprietaires', label: 'Propriétaires' },
    { cle: 'voyageurs', label: 'Comptes voyageurs' },
    { cle: 'reservationsMois', label: 'Demandes ce mois-ci' },
    { cle: 'sejoursMois', label: 'Séjours effectués ce mois-ci' },
]
</script>

<template>
    <Head title="Tableau de bord — Back-office" />

    <div ref="racine">
        <OfficeHead :kicker="aujourdhui" titre="Tableau de bord" lede="Ce qui attend quelqu'un, dans l'ordre où ça presse — puis l'état du catalogue." />

        <!-- ── La file ───────────────────────────────────────────────── -->
        <section aria-labelledby="file-t">
            <h2 id="file-t" class="sr-only">À traiter</h2>
            <ul class="tb__file">
                <li v-for="t in aTraiter" :key="t.cle" data-reveal>
                    <Link :href="t.href" class="tb__tuile" :class="{ 'is-due': t.nombre > 0 }">
                        <span class="tb__n of-num" :data-count="t.nombre">{{ nombre(t.nombre) }}</span>
                        <span class="tb__l">{{ t.label }}</span>
                        <span class="tb__d">{{ t.nombre > 0 ? t.detail : 'Rien en attente.' }}</span>
                        <span class="tb__go" aria-hidden="true"><OfficeIcon name="fleche" /></span>
                    </Link>
                </li>
            </ul>
        </section>

        <div class="tb__deux">
            <!-- ── Annonces à vérifier ──────────────────────────────── -->
            <section class="of-card" data-reveal aria-labelledby="av-t">
                <header class="of-card__h">
                    <h2 id="av-t" class="of-card__t">Annonces à vérifier</h2>
                    <Link href="/annonces?statut=submitted" class="of-card__lien">Toute la file</Link>
                </header>

                <ul v-if="annonces.length" class="of-rows">
                    <li v-for="a in annonces" :key="a.id">
                        <Link :href="`/annonces/${a.id}`" class="of-row tb__annonce">
                            <img v-if="a.cover" class="tb__vignette" :src="photoSrc(a.cover, 800)" alt="" width="64" height="48" loading="lazy" decoding="async">
                            <span v-else class="tb__vignette tb__vignette--vide" aria-hidden="true" />
                            <span class="tb__txt">
                                <span class="of-row__t">{{ a.title }}</span>
                                <span class="of-row__s">{{ a.owner?.name }} · {{ a.destination }}</span>
                            </span>
                            <span class="tb__meta">
                                <span class="of-chip" :class="a.owner?.verified ? 'of-chip--actif' : 'of-chip--attente'">
                                    {{ a.owner?.verified ? 'Numéro vérifié' : 'Numéro à vérifier' }}
                                </span>
                                <span class="tb__age">{{ ilYA(a.updatedAt) }}</span>
                            </span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="of-vide"><strong>Aucune fiche en attente.</strong>Les propriétaires envoient leur fiche depuis leur espace ; elle arrive ici.</p>
            </section>

            <!-- ── Demandes qui expirent ────────────────────────────── -->
            <section class="of-card" data-reveal aria-labelledby="de-t">
                <header class="of-card__h">
                    <h2 id="de-t" class="of-card__t">Demandes qui expirent</h2>
                    <Link href="/reservations?filtre=attente" class="of-card__lien">Toutes les demandes</Link>
                </header>

                <ul v-if="demandes.length" class="of-rows">
                    <li v-for="d in demandes" :key="d.reference">
                        <Link :href="`/reservations/${d.reference}`" class="of-row tb__demande">
                            <span class="tb__txt">
                                <span class="of-row__t">{{ d.listing?.title }}</span>
                                <span class="of-row__s">
                                    <span class="of-num">{{ d.reference }}</span> · {{ d.traveller }} ·
                                    {{ formatCompact(d.arrival) }} → {{ formatCompact(d.departure) }}
                                </span>
                            </span>
                            <span class="tb__reste">
                                <span class="tb__h of-num">{{ d.heuresRestantes }} h</span>
                                <!-- La mèche : ce qu'il reste des 48 h. Terre, parce
                                     que c'est un appel à agir — relancer le
                                     propriétaire avant que les nuits ne repartent. -->
                                <span class="tb__meche" aria-hidden="true">
                                    <span class="tb__meche-in" data-bar :style="{ transform: `scaleX(${meche(d.heuresRestantes)})` }" />
                                </span>
                            </span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="of-vide"><strong>Aucune demande n'expire sous 12 h.</strong>Au-delà, le rappel WhatsApp part tout seul au tiers du délai.</p>
            </section>
        </div>

        <div class="tb__deux tb__deux--bas">
            <!-- ── Le catalogue ─────────────────────────────────────── -->
            <section class="of-card" data-reveal aria-labelledby="ca-t">
                <header class="of-card__h">
                    <h2 id="ca-t" class="of-card__t">Le catalogue en ligne, par barreau</h2>
                </header>

                <div class="of-card__b">
                    <ol class="tb__echelle">
                        <li v-for="n in [...echelle].reverse()" :key="n.niveau" class="tb__barreau" :class="`tb__barreau--${n.niveau}`">
                            <span class="tb__niv of-num">{{ n.niveau }}</span>
                            <span class="tb__niv-l">{{ n.label }}</span>
                            <span class="tb__piste" aria-hidden="true">
                                <span class="tb__plein" data-bar :style="{ transform: `scaleX(${Math.max(n.nombre ? .03 : 0, n.nombre / plusHaut)})` }" />
                            </span>
                            <span class="tb__cpt of-num" :data-count="n.nombre">{{ n.nombre }}</span>
                        </li>
                    </ol>

                    <dl class="tb__chiffres">
                        <div v-for="c in CHIFFRES" :key="c.cle" class="tb__chiffre">
                            <dt>{{ c.label }}</dt>
                            <dd class="of-num" :data-count="chiffres[c.cle]">{{ nombre(chiffres[c.cle]) }}</dd>
                        </div>
                        <div class="tb__chiffre tb__chiffre--argent">
                            <dt>Commission du mois en cours</dt>
                            <dd class="of-num">{{ ariary(chiffres.commissionEncours) }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <!-- ── L'activité de l'équipe ───────────────────────────── -->
            <section class="of-card" data-reveal aria-labelledby="ac-t">
                <header class="of-card__h">
                    <h2 id="ac-t" class="of-card__t">Activité de l'équipe</h2>
                    <Link href="/journal" class="of-card__lien">Tout le journal</Link>
                </header>

                <ol v-if="activite.length" class="tb__fil">
                    <li v-for="a in activite" :key="a.id" class="tb__evt" :class="`tb__evt--${a.famille}`">
                        <span class="tb__evt-pt" aria-hidden="true" />
                        <p class="tb__evt-t">{{ a.summary }}</p>
                        <p class="tb__evt-s">{{ a.admin }} · {{ ilYA(a.at) }}</p>
                    </li>
                </ol>
                <p v-else class="of-vide"><strong>Rien encore.</strong>Chaque geste qui engage quelqu'un — publier, vérifier un numéro, annuler — laisse une ligne ici.</p>
            </section>
        </div>
    </div>
</template>

<style scoped>
/* ── La file ──────────────────────────────────────────────────────────── */
.tb__file {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(11.5rem, 1fr));
    gap: .75rem;
    margin: 0 0 1.5rem;
    padding: 0;
    list-style: none;
}

/* La tuile entière est le lien : on vise une file, pas un mot. */
.tb__tuile {
    position: relative;
    display: grid;
    align-content: start;
    gap: .2rem;
    height: 100%;
    min-height: 9.5rem;
    padding: 1.05rem 1.1rem 2.6rem;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    background: var(--white);
    color: inherit;
    text-decoration: none;
    transition: border-color .2s var(--ease), box-shadow .3s var(--ease), transform .3s var(--ease);
}
.tb__tuile::before {
    content: '';
    position: absolute;
    inset: 0 0 auto;
    height: 3px;
    background: var(--line-2);
}
.tb__tuile:hover { border-color: var(--line-2); box-shadow: var(--sh-2); transform: translateY(-2px); }
.tb__tuile:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }

.tb__n {
    font-size: clamp(2rem, 3.2vw, 2.6rem);
    font-weight: 800;
    letter-spacing: -.05em;
    line-height: 1;
    color: var(--text-3);
}
.tb__l { margin-top: .45rem; font-size: .88rem; font-weight: 700; line-height: 1.3; letter-spacing: -.015em; color: var(--ink); }
.tb__d { font-size: .78rem; line-height: 1.4; color: var(--text-3); }

.tb__go {
    position: absolute;
    left: 1.1rem;
    bottom: .85rem;
    display: grid;
    place-items: center;
    width: 1.8rem;
    height: 1.8rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    color: var(--text-3);
    transition: background-color .2s var(--ease), color .2s var(--ease), border-color .2s var(--ease), transform .3s var(--ease);
}
.tb__go .oi { width: .95rem; height: .95rem; }
.tb__tuile:hover .tb__go { transform: translateX(3px); }

/* **Ce qui attend prend la terre** : le filet du haut, le chiffre à l'encre
   pleine, et la flèche pleine. Une tuile à zéro reste grise — éteinte, pas
   absente. */
.tb__tuile.is-due::before { background: var(--terre-500); }
.tb__tuile.is-due .tb__n { color: var(--ink); }
.tb__tuile.is-due .tb__go { border-color: var(--terre-500); background: var(--terre-500); color: var(--white); }

/* ── Deux colonnes ────────────────────────────────────────────────────── */
.tb__deux {
    display: grid;
    grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}
.tb__deux--bas { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }

.tb__annonce { grid-template-columns: auto minmax(0, 1fr) auto; }
.tb__vignette {
    width: 4rem;
    height: 3rem;
    border-radius: var(--r-xs);
    object-fit: cover;
    background: var(--off-2);
}
.tb__vignette--vide { display: block; }
.tb__txt { min-width: 0; }
.tb__meta { display: grid; justify-items: end; gap: .25rem; }
.tb__age { font-size: .74rem; color: var(--text-3); white-space: nowrap; }

.tb__demande { grid-template-columns: minmax(0, 1fr) 5.5rem; }
.tb__reste { display: grid; justify-items: end; gap: .3rem; }
.tb__h { font-size: .95rem; font-weight: 800; color: var(--terre-700); }
.tb__meche {
    display: block;
    width: 100%;
    height: 4px;
    overflow: hidden;
    border-radius: 4px;
    background: var(--terre-100);
}
.tb__meche-in {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: var(--terre-500);
    transform-origin: left center;
}

/* ── L'échelle ────────────────────────────────────────────────────────── */
.tb__echelle { display: grid; gap: .55rem; margin: 0 0 1.25rem; padding: 0; list-style: none; }

.tb__barreau {
    display: grid;
    grid-template-columns: 1.6rem minmax(7.5rem, 10rem) minmax(0, 1fr) 2.2rem;
    align-items: center;
    gap: .65rem;
}
.tb__niv {
    display: grid;
    place-items: center;
    width: 1.6rem;
    height: 1.6rem;
    border-radius: 50%;
    background: var(--lagon-050);
    font-size: .76rem;
    font-weight: 800;
    color: var(--lagon-700);
}
.tb__niv-l { font-size: .84rem; font-weight: 600; color: var(--text-2); }
.tb__piste { display: block; height: .55rem; overflow: hidden; border-radius: .55rem; background: var(--off-2); }
.tb__plein { display: block; height: 100%; border-radius: inherit; background: var(--lagon-500); transform-origin: left center; }
.tb__cpt { font-size: .9rem; font-weight: 800; text-align: right; color: var(--ink); }

/* Le niveau 1 n'est pas une vérification : il garde le gris de « déclarée ». */
.tb__barreau--1 .tb__niv { background: var(--off-2); color: var(--text-2); }
.tb__barreau--1 .tb__plein { background: var(--unverified); }

.tb__chiffres {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
    gap: .6rem;
    margin: 0;
    padding-top: 1.1rem;
    border-top: 1px solid var(--line);
}
.tb__chiffre dt { font-size: .74rem; font-weight: 600; line-height: 1.3; color: var(--text-3); }
.tb__chiffre dd { margin: .15rem 0 0; font-size: 1.25rem; font-weight: 800; letter-spacing: -.035em; color: var(--ink); }
.tb__chiffre--argent dd { font-size: 1.05rem; }

/* ── Le fil d'activité ────────────────────────────────────────────────── */
.tb__fil { margin: 0; padding: .6rem 1.15rem 1rem; list-style: none; }

.tb__evt {
    position: relative;
    padding: .55rem 0 .55rem 1.3rem;
}
.tb__evt::before {
    content: '';
    position: absolute;
    left: .28rem;
    top: 1.2rem;
    bottom: -.55rem;
    width: 1px;
    background: var(--line);
}
.tb__evt:last-child::before { display: none; }
.tb__evt-pt {
    position: absolute;
    left: 0;
    top: .85rem;
    width: .6rem;
    height: .6rem;
    border: 2px solid var(--white);
    border-radius: 50%;
    background: var(--ink-3);
    box-shadow: 0 0 0 1px var(--line-2);
}
.tb__evt--annonces .tb__evt-pt { background: var(--terre-500); }
.tb__evt-t { margin: 0; font-size: .86rem; line-height: 1.4; color: var(--ink); }
.tb__evt-s { margin: .1rem 0 0; font-size: .74rem; color: var(--text-3); }

@media (max-width: 1100px) {
    .tb__deux, .tb__deux--bas { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 520px) {
    .tb__file { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .tb__tuile { min-height: 8.5rem; }
    .tb__annonce { grid-template-columns: auto minmax(0, 1fr); }
    .tb__meta { grid-column: 2; justify-items: start; grid-auto-flow: column; align-items: center; }
    .tb__barreau { grid-template-columns: 1.6rem minmax(0, 1fr) 2rem; }
    .tb__piste { grid-column: 2 / 4; grid-row: 2; }
}
</style>
