<script setup>
/**
 * La fiche d'une annonce, côté Vayla : **l'écran où « vérifié » prend son
 * sens.**
 *
 * À gauche ce que le propriétaire a déclaré, tel quel ; à droite ce que Vayla
 * en décide. La séparation est la page elle-même : on lit la fiche, on appelle,
 * on tranche — et chaque décision laisse une ligne au journal, en bas.
 *
 * **L'échelle se monte barreau par barreau, et chaque barreau fermé dit
 * pourquoi.** Les règles vivent dans `ModerationService` ; l'écran reçoit leurs
 * phrases toutes faites (`raison`) et les écrit sous le barreau, avant même
 * qu'on clique. Un bouton grisé sans explication ferait chercher ce qui manque
 * — ici, c'est « vérifiez d'abord le numéro », avec le bouton pour le faire
 * juste en dessous.
 *
 * **Changer de niveau se fait en deux temps** : choisir le barreau, puis
 * confirmer. C'est une promesse faite à tous les voyageurs qui liront la fiche ;
 * elle ne doit pas partir d'un clic égaré.
 *
 * **Un seul bouton plein : « Mettre en ligne ».** C'est le geste qui fait
 * avancer la file. Renvoyer et archiver sont bordés — ce sont des décisions,
 * pas le chemin normal — et chacun demande sa confirmation écrite.
 */
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import TrustGauge from '@/Components/TrustGauge.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { ariary, ilYA } from '@/Support/format.js'
import { photoSrc } from '@/Support/photo.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    annonce: { type: Object, required: true },
    proprietaire: { type: Object, default: null },
    niveaux: { type: Array, required: true },
    publication: { type: Object, required: true },
    journal: { type: Array, default: () => [] },
})

const racine = ref(null)
useOfficeMotion(racine)

const base = computed(() => `/annonces/${props.annonce.id}`)

// ── Publier ─────────────────────────────────────────────────────────────
const publier = useForm({})
const mettreEnLigne = () => publier.post(`${base.value}/publier`, { preserveScroll: true })

// ── Renvoyer / archiver : une décision écrite ───────────────────────────
const decision = ref(null) // 'renvoyer' | 'archiver' | null
const motif = useForm({ reason: '' })

const ouvrir = (quoi) => {
    motif.reset()
    motif.clearErrors()
    decision.value = decision.value === quoi ? null : quoi
}

const trancher = () => motif.post(`${base.value}/${decision.value}`, {
    preserveScroll: true,
    onSuccess: () => { decision.value = null },
})

// ── Le niveau ───────────────────────────────────────────────────────────
const choisi = ref(null)
const niveau = useForm({ level: null, note: '' })

const choisir = (n) => {
    if (n.actuel || n.raison) return
    choisi.value = choisi.value?.niveau === n.niveau ? null : n
}

const attribuer = () => {
    niveau.level = choisi.value.niveau
    niveau.post(`${base.value}/niveau`, {
        preserveScroll: true,
        onSuccess: () => { choisi.value = null; niveau.reset() },
    })
}

// ── Le numéro, vérifié par l'appel ──────────────────────────────────────
const verifier = useForm({})
const numeroVerifie = () => verifier.post(`/proprietaires/${props.proprietaire.id}/verifier`, { preserveScroll: true })

const echelle = computed(() => [...props.niveaux].reverse())

const PUCE = { submitted: 'of-chip--attente', published: 'of-chip--actif', draft: '', archived: 'of-chip--clos' }

const faits = computed(() => {
    const c = props.annonce.capacite
    const s = props.annonce.sejour

    return [
        ['Type', props.annonce.kind],
        ['Capacité', `${c.guests} pers. max`],
        ['Chambres', c.bedrooms],
        ['Couchages', c.beds],
        ['Salles d’eau', c.bathrooms],
        ['Surface', c.surface ? `${c.surface} m²` : '—'],
        ['Tarif', `${ariary(props.annonce.price)} / nuit`],
        ['Séjour', `${s.min ?? 1} à ${s.max ?? '∞'} nuits`],
        ['Arrivée', s.checkIn ? `dès ${s.checkIn}` : '—'],
        ['Départ', s.checkOut ? `avant ${s.checkOut}` : '—'],
        ['Animaux', s.pets ? 'Acceptés' : 'Non'],
        ['Fumeurs', s.smoking ? 'Acceptés' : 'Non'],
        ['Fêtes', s.events ? 'Acceptées' : 'Non'],
    ]
})
</script>

<template>
    <Head :title="`${annonce.title} — Back-office`" />

    <div ref="racine">
        <OfficeHead
            :back="{ href: '/annonces', label: 'Annonces' }"
            :kicker="`${annonce.destination ?? ''}${annonce.region ? ' · ' + annonce.region : ''}`"
            :titre="annonce.title"
        >
            <template #actions>
                <span class="of-chip" :class="PUCE[annonce.status]">{{ annonce.statusLabel }}</span>
                <span v-if="annonce.isDemo" class="of-chip of-chip--demo">Démo</span>
                <Link :href="`/annonces/${annonce.id}/modifier`" class="btn btn--sm btn--ink">
                    <OfficeIcon name="crayon" /> Modifier le contenu
                </Link>
                <a v-if="annonce.status === 'published'" :href="annonce.publicUrl" target="_blank" rel="noopener" class="btn btn--sm btn--outline">
                    <OfficeIcon name="externe" /> Voir sur le site
                </a>
            </template>
        </OfficeHead>

        <div class="fi">
            <!-- ═══ Ce que le propriétaire a déclaré ═══════════════════════ -->
            <div class="fi__main">
                <div v-if="annonce.reviewNote" class="fi__renvoi" data-reveal role="note">
                    <p class="of-kicker">Renvoyée au propriétaire</p>
                    <p class="fi__renvoi-p">{{ annonce.reviewNote }}</p>
                </div>

                <section class="of-card" data-reveal aria-labelledby="ph-t">
                    <header class="of-card__h">
                        <h2 id="ph-t" class="of-card__t">Photos</h2>
                        <span class="fi__compte of-num">{{ annonce.photos.length }}</span>
                    </header>
                    <ul v-if="annonce.photos.length" class="fi__pellicule">
                        <li v-for="(p, i) in annonce.photos" :key="p.key" class="fi__photo" :class="{ 'fi__photo--tete': i === 0 }">
                            <img :src="photoSrc(p, i === 0 ? 1600 : 800)" :alt="p.caption ?? ''" loading="lazy" decoding="async">
                            <span v-if="i === 0" class="fi__tag">Couverture</span>
                            <span v-if="p.isAi" class="fi__tag fi__tag--ia">Image générée</span>
                        </li>
                    </ul>
                    <p v-else class="of-vide"><strong>Aucune photo.</strong>Il en faut trois au moins pour envoyer une fiche.</p>
                </section>

                <section class="of-card" data-reveal aria-labelledby="fc-t">
                    <header class="of-card__h"><h2 id="fc-t" class="of-card__t">La fiche</h2></header>
                    <div class="of-card__b">
                        <p v-if="annonce.summary" class="fi__resume">{{ annonce.summary }}</p>
                        <dl class="fi__faits">
                            <div v-for="[k, v] in faits" :key="k" class="fi__fait">
                                <dt>{{ k }}</dt>
                                <dd class="of-num">{{ v ?? '—' }}</dd>
                            </div>
                        </dl>
                        <details class="fi__desc" :open="(annonce.description ?? '').length < 700">
                            <summary>Description <span class="of-num">({{ (annonce.description ?? '').length }} caractères)</span></summary>
                            <p>{{ annonce.description || 'Pas encore de description.' }}</p>
                        </details>
                    </div>
                </section>

                <section class="of-card" data-reveal aria-labelledby="eq-t">
                    <header class="of-card__h">
                        <h2 id="eq-t" class="of-card__t">Équipements déclarés</h2>
                        <span class="fi__legende"><span class="fi__etoile" aria-hidden="true" /> mis en avant sur la carte</span>
                    </header>
                    <div v-if="annonce.equipements.length" class="of-card__b fi__groupes">
                        <div v-for="g in annonce.equipements" :key="g.label" class="fi__groupe">
                            <p class="of-kicker">{{ g.label }}</p>
                            <ul class="fi__equip">
                                <li v-for="e in g.items" :key="e.label" :class="{ 'is-avant': e.highlight }">
                                    <span v-if="e.highlight" class="fi__etoile" aria-hidden="true" />
                                    {{ e.label }}<span v-if="e.highlight" class="sr-only"> (mis en avant)</span>
                                    <em v-if="e.note"> — {{ e.note }}</em>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <p v-else class="of-vide"><strong>Aucun équipement coché.</strong></p>
                </section>

                <section class="of-card" data-reveal aria-labelledby="jo-t">
                    <header class="of-card__h"><h2 id="jo-t" class="of-card__t">Décisions sur cette annonce</h2></header>
                    <ol v-if="journal.length" class="fi__journal">
                        <li v-for="j in journal" :key="j.id">
                            <p class="fi__j-t">{{ j.summary }}</p>
                            <p v-if="j.note" class="fi__j-note">« {{ j.note }} »</p>
                            <p class="fi__j-s">{{ j.admin }} · {{ ilYA(j.at) }}</p>
                        </li>
                    </ol>
                    <p v-else class="of-vide">Aucune décision encore : l'annonce n'a pas été touchée par l'équipe.</p>
                </section>
            </div>

            <!-- ═══ Ce que Vayla en décide ════════════════════════════════ -->
            <aside class="fi__side">
                <!-- La décision -->
                <section class="of-card fi__decision" data-reveal aria-labelledby="dc-t">
                    <header class="of-card__h"><h2 id="dc-t" class="of-card__t">Décision</h2></header>
                    <div class="of-card__b">
                        <button type="button" class="btn btn--terre fi__pleine" :disabled="!publication.possible || publier.processing" @click="mettreEnLigne">
                            {{ publier.processing ? 'Mise en ligne…' : (annonce.status === 'archived' ? 'Remettre en ligne' : 'Mettre en ligne') }}
                        </button>
                        <p v-if="publication.raison" class="fi__pourquoi">{{ publication.raison }}</p>

                        <div class="fi__deux">
                            <button v-if="publication.renvoyable" type="button" class="btn btn--sm btn--outline" :aria-expanded="decision === 'renvoyer'" @click="ouvrir('renvoyer')">
                                Renvoyer au propriétaire
                            </button>
                            <button v-if="publication.archivable" type="button" class="btn btn--sm btn--outline" :aria-expanded="decision === 'archiver'" @click="ouvrir('archiver')">
                                Archiver
                            </button>
                        </div>

                        <form v-if="decision" class="fi__motif" @submit.prevent="trancher">
                            <div class="of-field">
                                <label class="of-label" for="motif">
                                    {{ decision === 'renvoyer' ? 'Ce que le propriétaire doit reprendre' : 'Pourquoi l’archiver (facultatif)' }}
                                </label>
                                <textarea id="motif" v-model="motif.reason" class="of-input" rows="4" maxlength="1000"
                                          :required="decision === 'renvoyer'"
                                          :placeholder="decision === 'renvoyer' ? 'Ex. : la photo de la chambre 2 est floue, et le groupe électrogène n’est pas déclaré.' : 'Ex. : le propriétaire ne loue plus ce logement.'" />
                                <p class="of-help">
                                    {{ decision === 'renvoyer'
                                        ? 'Il lit ce texte tel quel dans son espace, en tête de sa fiche.'
                                        : 'L’annonce quitte le site aussitôt. Les séjours déjà engagés restent engagés.' }}
                                </p>
                                <p v-if="motif.errors.reason" class="of-err" role="alert">{{ motif.errors.reason }}</p>
                            </div>
                            <p v-if="decision === 'archiver' && annonce.aVenir" class="fi__attention">
                                <OfficeIcon name="alerte" /> {{ annonce.aVenir }} séjour{{ annonce.aVenir > 1 ? 's' : '' }} à venir sur ce logement.
                            </p>
                            <div class="fi__deux">
                                <button type="submit" class="btn btn--sm btn--ink" :disabled="motif.processing">
                                    {{ decision === 'renvoyer' ? 'Renvoyer la fiche' : 'Confirmer l’archivage' }}
                                </button>
                                <button type="button" class="btn btn--sm btn--ghost" @click="decision = null">Annuler</button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- L'échelle -->
                <section class="of-card" data-reveal aria-labelledby="nv-t">
                    <header class="of-card__h">
                        <h2 id="nv-t" class="of-card__t">Niveau de confiance</h2>
                        <TrustGauge :level="annonce.trustLevel" />
                    </header>

                    <ol class="fi__echelle">
                        <li v-for="n in echelle" :key="n.niveau">
                            <button
                                type="button"
                                class="fi__barreau"
                                :class="{ 'is-actuel': n.actuel, 'is-choisi': choisi?.niveau === n.niveau, 'is-ferme': !n.actuel && n.raison, [`fi__barreau--${n.niveau}`]: true }"
                                :aria-pressed="choisi?.niveau === n.niveau"
                                :aria-disabled="n.actuel || !!n.raison"
                                @click="choisir(n)"
                            >
                                <span class="fi__b-n of-num">{{ n.niveau }}</span>
                                <span class="fi__b-txt">
                                    <span class="fi__b-l">{{ n.label }}<span v-if="n.actuel" class="fi__b-ici"> · actuel</span></span>
                                    <span class="fi__b-s">{{ n.summary }}</span>
                                    <span v-if="!n.actuel && n.raison" class="fi__b-raison">{{ n.raison }}</span>
                                </span>
                            </button>
                        </li>
                    </ol>

                    <form v-if="choisi" class="fi__confirmer" @submit.prevent="attribuer">
                        <p class="fi__conf-t">
                            Passer au niveau <strong class="of-num">{{ choisi.niveau }}</strong> — {{ choisi.label }}
                        </p>
                        <div class="of-field">
                            <label class="of-label" for="note-niveau">Ce qui a été vérifié (facultatif)</label>
                            <input id="note-niveau" v-model="niveau.note" class="of-input" maxlength="500" placeholder="Ex. : visio de 20 min, toutes les pièces vues.">
                        </div>
                        <div class="fi__deux">
                            <button type="submit" class="btn btn--sm btn--ink" :disabled="niveau.processing">Confirmer le niveau {{ choisi.niveau }}</button>
                            <button type="button" class="btn btn--sm btn--ghost" @click="choisi = null">Annuler</button>
                        </div>
                    </form>
                </section>

                <!-- Le propriétaire -->
                <section v-if="proprietaire" class="of-card" data-reveal aria-labelledby="pr-t">
                    <header class="of-card__h">
                        <h2 id="pr-t" class="of-card__t">Propriétaire</h2>
                        <Link :href="`/proprietaires/${proprietaire.id}`" class="of-card__lien">Sa fiche</Link>
                    </header>
                    <div class="of-card__b fi__prop">
                        <p class="fi__prop-nom">{{ proprietaire.name }}</p>
                        <p class="fi__prop-s">{{ proprietaire.email }}<template v-if="proprietaire.city"> · {{ proprietaire.city }}</template></p>

                        <template v-if="proprietaire.telephone">
                            <p class="fi__tel of-num">{{ proprietaire.telephone.lisible }}<span v-if="proprietaire.telephone.operateur" class="fi__op"> · {{ proprietaire.telephone.operateur }}</span></p>
                            <div class="fi__deux">
                                <a :href="proprietaire.telephone.tel" class="btn btn--sm btn--outline"><OfficeIcon name="appel" /> Appeler</a>
                                <a v-if="proprietaire.telephone.whatsapp" :href="proprietaire.telephone.whatsapp" target="_blank" rel="noopener" class="btn btn--sm btn--outline"><OfficeIcon name="whatsapp" /> WhatsApp</a>
                            </div>
                        </template>

                        <p v-if="proprietaire.verified" class="fi__verifie"><OfficeIcon name="coche" /> Numéro vérifié par appel</p>
                        <div v-else class="fi__averifier">
                            <p class="fi__av-t">Numéro pas encore vérifié</p>
                            <p class="of-help">Appuyez seulement après avoir eu le propriétaire au bout du fil : c'est ce qui ouvre le niveau 2.</p>
                            <button type="button" class="btn btn--sm btn--ink" :disabled="verifier.processing" @click="numeroVerifie">
                                Je l'ai eu au téléphone
                            </button>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.fi {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(19rem, 23rem);
    align-items: start;
    gap: 1rem;
}
.fi__main, .fi__side { display: grid; gap: 1rem; min-width: 0; }
.fi__side { position: sticky; top: 1.25rem; }

.fi__renvoi {
    padding: .85rem 1rem;
    border: 1px solid var(--terre-200);
    border-left: 3px solid var(--terre-500);
    border-radius: var(--r-sm);
    background: var(--terre-050);
}
.fi__renvoi-p { margin: .3rem 0 0; font-size: .9rem; line-height: 1.55; color: var(--ink); white-space: pre-line; }

.fi__compte { font-size: .82rem; font-weight: 800; color: var(--text-3); }

/* La pellicule : la couverture en grand, les autres en file. */
.fi__pellicule {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(8.5rem, 1fr));
    gap: .45rem;
    margin: 0;
    padding: .9rem 1.15rem 1.1rem;
    list-style: none;
}
.fi__photo {
    position: relative;
    overflow: hidden;
    aspect-ratio: 4 / 3;
    border-radius: var(--r-xs);
    background: var(--off-2);
}
.fi__photo--tete { grid-column: span 2; grid-row: span 2; }
.fi__photo img { width: 100%; height: 100%; object-fit: cover; }
.fi__tag {
    position: absolute;
    left: .4rem;
    bottom: .4rem;
    padding: .15rem .45rem;
    border-radius: var(--r-pill);
    background: rgba(26, 21, 18, .78);
    font-size: .64rem;
    font-weight: 700;
    color: var(--white);
}
.fi__tag--ia { left: auto; right: .4rem; }

.fi__resume { margin: 0 0 1rem; font-size: .98rem; font-weight: 600; line-height: 1.5; color: var(--ink); }

.fi__faits {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(8.5rem, 1fr));
    gap: .1rem 1rem;
    margin: 0;
}
.fi__fait { padding: .5rem 0; border-top: 1px solid var(--line); }
.fi__fait dt { font-size: .72rem; font-weight: 600; color: var(--text-3); }
.fi__fait dd { margin: .05rem 0 0; font-size: .9rem; font-weight: 700; color: var(--ink); }

.fi__desc { margin-top: 1rem; border-top: 1px solid var(--line); padding-top: .75rem; }
.fi__desc summary {
    min-height: 2.5rem;
    display: flex;
    align-items: center;
    gap: .35rem;
    font-size: .86rem;
    font-weight: 700;
    color: var(--ink);
    cursor: pointer;
}
.fi__desc summary span { font-weight: 500; color: var(--text-3); }
.fi__desc p { margin: .35rem 0 0; font-size: .9rem; line-height: 1.65; color: var(--text-2); white-space: pre-line; }

.fi__legende { display: inline-flex; align-items: center; gap: .35rem; font-size: .74rem; color: var(--text-3); }
.fi__etoile {
    display: inline-block;
    width: .5rem;
    height: .5rem;
    border-radius: 50%;
    background: var(--terre-500);
}
.fi__groupes { display: grid; grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr)); gap: 1rem 1.5rem; }
.fi__equip { display: grid; gap: .2rem; margin: .35rem 0 0; padding: 0; list-style: none; font-size: .86rem; color: var(--text-2); }
.fi__equip li { display: flex; align-items: baseline; gap: .35rem; }
.fi__equip li.is-avant { font-weight: 700; color: var(--ink); }
.fi__equip em { font-style: normal; color: var(--text-3); }

.fi__journal { margin: 0; padding: .4rem 1.15rem .9rem; list-style: none; }
.fi__journal li { padding: .6rem 0; border-top: 1px solid var(--line); }
.fi__journal li:first-child { border-top: 0; }
.fi__j-t { margin: 0; font-size: .86rem; font-weight: 600; color: var(--ink); }
.fi__j-note { margin: .2rem 0 0; font-size: .84rem; color: var(--text-2); }
.fi__j-s { margin: .15rem 0 0; font-size: .74rem; color: var(--text-3); }

/* ── La décision ─────────────────────────────────────────────────────── */
.fi__pleine { width: 100%; }
.fi__pourquoi { margin: .5rem 0 0; font-size: .8rem; line-height: 1.45; color: var(--text-2); }
.fi__deux { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .75rem; }
.fi__deux .btn { flex: 1 1 auto; }
.fi__motif { margin-top: .9rem; padding-top: .9rem; border-top: 1px solid var(--line); }
.fi__attention {
    display: flex;
    align-items: center;
    gap: .4rem;
    margin: .6rem 0 0;
    font-size: .8rem;
    font-weight: 700;
    color: var(--terre-700);
}
.fi__attention .oi { width: 1rem; height: 1rem; }

/* ── L'échelle ───────────────────────────────────────────────────────────
   Les barreaux se lisent du haut vers le bas, 4 en tête : on monte une
   échelle. Le barreau atteint prend le lagon — c'est une vérification, le seul
   endroit de l'écran où il a le droit d'être. */
.fi__echelle { display: grid; gap: .35rem; margin: 0; padding: .8rem; list-style: none; }

.fi__barreau {
    display: grid;
    grid-template-columns: 1.9rem minmax(0, 1fr);
    gap: .65rem;
    width: 100%;
    padding: .65rem .7rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-sm);
    background: var(--white);
    font: inherit;
    text-align: left;
    cursor: pointer;
    transition: border-color .2s var(--ease), background-color .2s var(--ease), box-shadow .2s var(--ease);
}
.fi__barreau:hover { border-color: var(--ink); }
.fi__barreau:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }

.fi__b-n {
    display: grid;
    place-items: center;
    width: 1.9rem;
    height: 1.9rem;
    border: 1.5px solid var(--line-2);
    border-radius: 50%;
    font-size: .84rem;
    font-weight: 800;
    color: var(--text-2);
}
.fi__b-txt { display: grid; gap: .1rem; min-width: 0; }
.fi__b-l { font-size: .88rem; font-weight: 700; color: var(--ink); }
.fi__b-ici { font-weight: 600; color: var(--lagon-700); }
.fi__b-s { font-size: .76rem; line-height: 1.4; color: var(--text-3); }
.fi__b-raison { margin-top: .25rem; font-size: .76rem; font-weight: 600; line-height: 1.4; color: var(--terre-700); }

.fi__barreau.is-actuel { border-color: var(--lagon-500); background: var(--lagon-050); cursor: default; }
.fi__barreau.is-actuel .fi__b-n { border-color: var(--lagon-500); background: var(--lagon-500); color: var(--white); }
.fi__barreau--1.is-actuel { border-color: var(--unverified); background: var(--off); }
.fi__barreau--1.is-actuel .fi__b-n { border-color: var(--unverified); background: var(--unverified); }
.fi__barreau--1 .fi__b-ici { color: var(--text-2); }

/* Fermé : il se lit, il ne s'efface pas — la raison est écrite dessous. */
.fi__barreau.is-ferme { background: var(--off); cursor: not-allowed; }
.fi__barreau.is-ferme:hover { border-color: var(--line-2); }
.fi__barreau.is-ferme .fi__b-l { color: var(--ink-3); }

.fi__barreau.is-choisi { border-color: var(--ink); box-shadow: 0 0 0 1px var(--ink); }
.fi__barreau.is-choisi .fi__b-n { border-color: var(--ink); background: var(--ink); color: var(--white); }

.fi__confirmer { margin: 0 .8rem .9rem; padding: .85rem; border-radius: var(--r-sm); background: var(--off-2); display: grid; gap: .6rem; }
.fi__confirmer .fi__deux { margin-top: 0; }
.fi__conf-t { margin: 0; font-size: .88rem; color: var(--ink); }

/* ── Le propriétaire ─────────────────────────────────────────────────── */
.fi__prop-nom { margin: 0; font-size: .98rem; font-weight: 800; letter-spacing: -.02em; color: var(--ink); }
.fi__prop-s { margin: .1rem 0 0; font-size: .8rem; color: var(--text-3); overflow-wrap: anywhere; }
.fi__tel { margin: .7rem 0 0; font-size: 1rem; font-weight: 700; color: var(--ink); }
.fi__op { font-weight: 500; color: var(--text-3); }
.fi__prop .btn .oi { width: 1rem; height: 1rem; }

/* « Vérifié » : c'est une vérification — la coche prend le lagon. */
.fi__verifie {
    display: flex;
    align-items: center;
    gap: .4rem;
    margin: .85rem 0 0;
    font-size: .84rem;
    font-weight: 700;
    color: var(--lagon-700);
}
.fi__verifie .oi { width: 1.05rem; height: 1.05rem; stroke-width: 2.2; }

.fi__averifier { margin-top: .85rem; padding: .8rem; border: 1px dashed var(--terre-300); border-radius: var(--r-sm); background: var(--terre-050); display: grid; gap: .45rem; justify-items: start; }
.fi__av-t { margin: 0; font-size: .86rem; font-weight: 800; color: var(--terre-700); }

@media (max-width: 1080px) {
    .fi { grid-template-columns: minmax(0, 1fr); }
    .fi__side { position: static; order: -1; }
}
</style>
