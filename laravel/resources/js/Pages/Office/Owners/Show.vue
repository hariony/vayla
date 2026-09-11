<script setup>
/**
 * Un propriétaire : qui il est, ce qu'il loue, ce qu'il doit.
 *
 * **Les deux gestes de la vérification sont en tête** — appeler, puis
 * confirmer le numéro. Le bouton ne dit pas « Vérifier » mais « Je l'ai eu au
 * téléphone » : il enregistre un appel qui a eu lieu, pas une intention.
 *
 * **Le lien d'accès se renvoie par la file WhatsApp, jamais par ici** : il
 * ouvre l'espace du propriétaire, et l'afficher dans le back-office en ferait
 * une chaîne qu'on copie-colle n'importe où. Le bouton le met en file ; la
 * file le fait partir, et le journal garde qui l'a demandé.
 *
 * **L'adresse exacte n'apparaît qu'ici** — elle sert à la facture et à la
 * visite, jamais publiée.
 */
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import TrustGauge from '@/Components/TrustGauge.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { formatCompact } from '@/Composables/useStayDates.js'
import { ariary, ilYA } from '@/Support/format.js'
import { photoSrc } from '@/Support/photo.js'
import { portraitSrc } from '@/Support/portrait.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    proprietaire: { type: Object, required: true },
    annonces: { type: Array, default: () => [] },
    reservations: { type: Array, default: () => [] },
    factures: { type: Object, required: true },
    journal: { type: Array, default: () => [] },
})

const racine = ref(null)
useOfficeMotion(racine)

const verifier = useForm({})
const lien = useForm({})
const base = `/proprietaires/${props.proprietaire.id}`

const initiales = (nom = '') => nom.trim().split(/\s+/).slice(0, 2).map((m) => m[0] ?? '').join('').toUpperCase()

const PUCE_A = { submitted: 'of-chip--attente', published: 'of-chip--actif', draft: '', archived: 'of-chip--clos' }
const PUCE_R = { pending: 'of-chip--attente', accepted: 'of-chip--actif', completed: 'of-chip--actif' }
</script>

<template>
    <Head :title="`${proprietaire.name} — Back-office`" />

    <div ref="racine">
        <OfficeHead :back="{ href: '/proprietaires', label: 'Propriétaires' }" kicker="Propriétaire" :titre="proprietaire.name">
            <template #lede>
                {{ proprietaire.email }}<template v-if="proprietaire.city"> · {{ proprietaire.city }}</template>
                <template v-if="proprietaire.lastLoginAt"> · dernière connexion {{ ilYA(proprietaire.lastLoginAt) }}</template>
            </template>
            <template #actions>
                <span v-if="proprietaire.isDemo" class="of-chip of-chip--demo">Démo</span>
            </template>
        </OfficeHead>

        <div class="ps">
            <aside class="ps__side">
                <section class="of-card ps__id" data-reveal>
                    <div class="of-card__b">
                        <img v-if="proprietaire.portrait" class="ps__av" :src="portraitSrc(proprietaire.portrait)" alt="" width="72" height="72">
                        <span v-else class="ps__av ps__av--init" aria-hidden="true">{{ initiales(proprietaire.name) }}</span>

                        <p class="ps__tel of-num">{{ proprietaire.telephone?.lisible ?? 'Pas de numéro' }}</p>
                        <p v-if="proprietaire.telephone?.operateur" class="ps__op">{{ proprietaire.telephone.operateur }}</p>

                        <div v-if="proprietaire.telephone" class="ps__btns">
                            <a :href="proprietaire.telephone.tel" class="btn btn--sm btn--outline"><OfficeIcon name="appel" /> Appeler</a>
                            <a v-if="proprietaire.telephone.whatsapp" :href="proprietaire.telephone.whatsapp" target="_blank" rel="noopener" class="btn btn--sm btn--outline"><OfficeIcon name="whatsapp" /> WhatsApp</a>
                        </div>

                        <p v-if="proprietaire.verified" class="ps__verifie"><OfficeIcon name="coche" /> Numéro vérifié par appel</p>
                        <div v-else class="ps__averifier">
                            <p class="ps__av-t">Numéro pas encore vérifié</p>
                            <p class="of-help">Appuyez après avoir eu la personne au bout du fil : ses annonces pourront passer au niveau 2.</p>
                            <button type="button" class="btn btn--sm btn--ink" :disabled="verifier.processing" @click="verifier.post(`${base}/verifier`, { preserveScroll: true })">
                                Je l'ai eu au téléphone
                            </button>
                        </div>
                    </div>
                </section>

                <section class="of-card" data-reveal aria-labelledby="co-t">
                    <header class="of-card__h"><h2 id="co-t" class="of-card__t">Coordonnées</h2></header>
                    <dl class="of-card__b ps__dl">
                        <div><dt>Adresse exacte</dt><dd>{{ proprietaire.address || 'Pas encore renseignée' }}</dd></div>
                        <div><dt>Mobile money</dt><dd class="of-num">{{ proprietaire.mobileMoney ? `${proprietaire.operator ?? ''} ${proprietaire.mobileMoney}` : 'Pas encore renseigné' }}</dd></div>
                        <div><dt>Adresse e-mail</dt><dd>{{ proprietaire.email }} <span v-if="proprietaire.emailVerified" class="ps__ok">· prouvée par code</span></dd></div>
                        <div><dt>Inscrit</dt><dd>{{ ilYA(proprietaire.createdAt) }}</dd></div>
                    </dl>
                </section>

                <section class="of-card" data-reveal aria-labelledby="li-t">
                    <header class="of-card__h"><h2 id="li-t" class="of-card__t">Lien d'accès WhatsApp</h2></header>
                    <div class="of-card__b">
                        <p class="of-help">Il ouvre son espace en un geste. Le renvoyer le remet dans la file WhatsApp, prêt à partir — la clé ne change pas.</p>
                        <button type="button" class="btn btn--sm btn--outline ps__plein" :disabled="lien.processing" @click="lien.post(`${base}/lien`, { preserveScroll: true })">
                            Renvoyer le lien d'accès
                        </button>
                    </div>
                </section>
            </aside>

            <div class="ps__main">
                <section class="of-card" data-reveal aria-labelledby="an-t">
                    <header class="of-card__h">
                        <h2 id="an-t" class="of-card__t">Logements <span class="ps__n of-num">{{ annonces.length }}</span></h2>
                        <!-- Pour le propriétaire qui dicte sa fiche au
                             téléphone : l'annonce naît en brouillon, à son nom. -->
                        <Link :href="`/proprietaires/${proprietaire.id}/annonces/nouvelle`" class="of-card__lien">Saisir une annonce pour lui</Link>
                    </header>
                    <ul v-if="annonces.length" class="of-rows">
                        <li v-for="a in annonces" :key="a.id">
                            <Link :href="`/annonces/${a.id}`" class="of-row ps__annonce">
                                <img v-if="a.cover" class="ps__vignette" :src="photoSrc(a.cover, 800)" alt="" width="64" height="48" loading="lazy">
                                <span v-else class="ps__vignette" aria-hidden="true" />
                                <span class="ps__txt">
                                    <span class="of-row__t">{{ a.title }}</span>
                                    <span class="of-row__s">{{ a.destination }} · <span class="of-num">{{ ariary(a.price) }}</span>/nuit</span>
                                </span>
                                <TrustGauge :level="a.trustLevel" />
                                <span class="of-chip" :class="PUCE_A[a.status]">{{ a.statusLabel }}</span>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="of-vide">Aucun logement saisi pour l'instant.</p>
                </section>

                <section class="of-card" data-reveal aria-labelledby="fa-t">
                    <header class="of-card__h">
                        <h2 id="fa-t" class="of-card__t">Factures</h2>
                        <Link href="/facturation" class="of-card__lien">Facturation du mois</Link>
                    </header>
                    <div class="of-card__b">
                        <p class="ps__encours">
                            <span class="of-kicker">Ce mois-ci, pas encore une facture</span>
                            <span class="ps__encours-n of-num">{{ ariary(factures.encours.due) }}</span>
                            <span class="ps__encours-s">{{ factures.encours.stays }} séjour{{ factures.encours.stays > 1 ? 's' : '' }} confirmé{{ factures.encours.stays > 1 ? 's' : '' }}</span>
                        </p>
                    </div>
                    <ul v-if="factures.passees.length" class="of-rows">
                        <li v-for="f in factures.passees" :key="f.mois" class="of-row ps__facture">
                            <span class="ps__mois">{{ f.label }}</span>
                            <span class="of-num ps__du">{{ ariary(f.due) }}</span>
                            <span class="of-chip" :class="f.settlement ? 'of-chip--actif' : 'of-chip--attente'">
                                {{ f.settlement ? `Réglée ${ilYA(f.settlement.at)}` : 'À régler' }}
                            </span>
                        </li>
                    </ul>
                    <p v-else class="of-vide">Aucune facture : pas encore de séjour confirmé.</p>
                </section>

                <section class="of-card" data-reveal aria-labelledby="re-t">
                    <header class="of-card__h"><h2 id="re-t" class="of-card__t">Dernières réservations</h2></header>
                    <ul v-if="reservations.length" class="of-rows">
                        <li v-for="r in reservations" :key="r.reference">
                            <Link :href="`/reservations/${r.reference}`" class="of-row ps__resa">
                                <span class="of-num ps__ref">{{ r.reference }}</span>
                                <span class="ps__txt">
                                    <span class="of-row__t">{{ r.traveller }}</span>
                                    <span class="of-row__s">{{ r.listing?.title }} · {{ formatCompact(r.arrival) }} → {{ formatCompact(r.departure) }}</span>
                                </span>
                                <span class="of-chip" :class="PUCE_R[r.status] ?? 'of-chip--clos'">{{ r.statusLabel }}</span>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="of-vide">Aucune réservation.</p>
                </section>

                <section v-if="journal.length" class="of-card" data-reveal aria-labelledby="jo-t">
                    <header class="of-card__h"><h2 id="jo-t" class="of-card__t">Gestes de l'équipe</h2></header>
                    <ol class="ps__journal">
                        <li v-for="j in journal" :key="j.id">
                            <p class="ps__j-t">{{ j.summary }}</p>
                            <p class="ps__j-s">{{ j.admin }} · {{ ilYA(j.at) }}</p>
                        </li>
                    </ol>
                </section>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ps {
    display: grid;
    grid-template-columns: minmax(17rem, 20rem) minmax(0, 1fr);
    align-items: start;
    gap: 1rem;
}
.ps__side, .ps__main { display: grid; gap: 1rem; min-width: 0; }
.ps__side { position: sticky; top: 1.25rem; }

.ps__id .of-card__b { display: grid; justify-items: start; }
.ps__av { width: 4.5rem; height: 4.5rem; border-radius: 50%; object-fit: cover; background: var(--off-2); }
.ps__av--init { display: grid; place-items: center; background: var(--ink); color: var(--white); font-size: 1.2rem; font-weight: 800; }
.ps__tel { margin: .9rem 0 0; font-size: 1.1rem; font-weight: 800; letter-spacing: -.02em; color: var(--ink); }
.ps__op { margin: 0; font-size: .78rem; color: var(--text-3); }
.ps__btns { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .7rem; }
.ps__btns .btn .oi, .ps__verifie .oi { width: 1rem; height: 1rem; }

.ps__verifie { display: flex; align-items: center; gap: .4rem; margin: .9rem 0 0; font-size: .84rem; font-weight: 700; color: var(--lagon-700); }
.ps__verifie .oi { stroke-width: 2.2; }
.ps__averifier { display: grid; gap: .45rem; justify-items: start; margin-top: .9rem; padding: .8rem; border: 1px dashed var(--terre-300); border-radius: var(--r-sm); background: var(--terre-050); }
.ps__av-t { margin: 0; font-size: .86rem; font-weight: 800; color: var(--terre-700); }

.ps__dl { display: grid; gap: .7rem; margin: 0; }
.ps__dl dt { font-size: .72rem; font-weight: 600; color: var(--text-3); }
.ps__dl dd { margin: .05rem 0 0; font-size: .86rem; color: var(--ink); overflow-wrap: anywhere; }
.ps__ok { color: var(--text-3); }
.ps__plein { width: 100%; margin-top: .7rem; }

.ps__n { font-size: .82rem; font-weight: 800; color: var(--text-3); }
.ps__annonce { grid-template-columns: auto minmax(0, 1fr) auto auto; }
.ps__vignette { width: 4rem; height: 3rem; border-radius: var(--r-xs); object-fit: cover; background: var(--off-2); }
.ps__txt { min-width: 0; }

.ps__encours { display: grid; gap: .1rem; margin: 0; }
.ps__encours-n { font-size: 1.5rem; font-weight: 800; letter-spacing: -.04em; color: var(--ink); }
.ps__encours-s { font-size: .8rem; color: var(--text-3); }

.ps__facture { grid-template-columns: minmax(0, 1fr) auto auto; }
.ps__mois { font-size: .9rem; font-weight: 700; color: var(--ink); text-transform: capitalize; }
.ps__du { font-size: .9rem; font-weight: 800; color: var(--ink); }

.ps__resa { grid-template-columns: 5.4rem minmax(0, 1fr) auto; }
.ps__ref { font-size: .82rem; font-weight: 800; color: var(--ink); }

.ps__journal { margin: 0; padding: .4rem 1.15rem .9rem; list-style: none; }
.ps__journal li { padding: .55rem 0; border-top: 1px solid var(--line); }
.ps__journal li:first-child { border-top: 0; }
.ps__j-t { margin: 0; font-size: .86rem; color: var(--ink); }
.ps__j-s { margin: .1rem 0 0; font-size: .74rem; color: var(--text-3); }

@media (max-width: 1000px) {
    .ps { grid-template-columns: minmax(0, 1fr); }
    .ps__side { position: static; }
}
@media (max-width: 560px) {
    .ps__annonce { grid-template-columns: auto minmax(0, 1fr); }
    .ps__annonce > :nth-child(n+3) { grid-column: 2; justify-self: start; }
    .ps__resa { grid-template-columns: minmax(0, 1fr) auto; }
    .ps__ref { grid-column: 1 / -1; }
}
</style>
