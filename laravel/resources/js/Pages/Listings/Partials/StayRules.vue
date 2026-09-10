<script setup>
/**
 * « À savoir avant de demander ».
 *
 * Trois colonnes, comme le « À savoir » d'Airbnb — mais la troisième dit
 * l'inverse de la sienne. Là où une plateforme qui encaisse affiche sa
 * politique d'annulation, Vayla n'a rien à annuler : **aucun argent ne
 * transite**. L'écrire noir sur blanc vaut mieux qu'une colonne vide pour
 * ressembler aux autres.
 *
 * Tout vient de colonnes, jamais d'un paragraphe : « pas d'animaux » doit
 * pouvoir devenir un filtre le jour où un voyageur le demande.
 */
import { computed } from 'vue'
import AmenityIcon from '@/Components/AmenityIcon.vue'

const props = defineProps({
    rules: { type: Object, required: true },
})

const reglement = computed(() => {
    const r = props.rules
    const out = [
        { icon: 'key', txt: `Arrivée à partir de ${r.checkInFrom}` },
        { icon: 'step', txt: `Départ avant ${r.checkOutBefore}` },
        { icon: 'baby', txt: `${r.guests} voyageurs maximum` },
        { icon: 'book', txt: r.minNights > 1 ? `${r.minNights} nuits minimum` : 'À partir d\'une nuit' },
    ]

    if (r.maxNights) out.push({ icon: 'book', txt: `${r.maxNights} nuits maximum` })

    // L'interdiction se dit autant que l'autorisation : une liste qui ne
    // contiendrait que les « oui » laisserait deviner le reste.
    out.push({ icon: 'guide', txt: r.pets ? 'Animaux acceptés' : 'Pas d\'animaux' })
    out.push({ icon: 'flame', txt: r.smoking ? 'Fumeurs acceptés' : 'Logement non-fumeur' })
    out.push({ icon: 'speaker', txt: r.events ? 'Fêtes possibles, à convenir' : 'Pas de fête ni d\'événement' })

    return out
})
</script>

<template>
    <section class="sr" data-anim>
        <h2 class="sr__h2">À savoir avant de demander</h2>

        <div class="sr__cols">
            <div class="sr__col">
                <h3 class="sr__title">Règlement du logement</h3>
                <ul class="sr__list">
                    <li v-for="r in reglement" :key="r.txt">
                        <AmenityIcon :name="r.icon" />
                        {{ r.txt }}
                    </li>
                </ul>
            </div>

            <div class="sr__col">
                <h3 class="sr__title">Comment ça se passe</h3>
                <ol class="sr__steps">
                    <li>
                        <span class="sr__n num">1</span>
                        Vous déposez une demande avec vos dates. C'est gratuit et
                        sans engagement.
                    </li>
                    <li>
                        <span class="sr__n num">2</span>
                        Nous transmettons au propriétaire et vous mettons en
                        relation directe.
                    </li>
                    <li>
                        <span class="sr__n num">3</span>
                        Vous convenez ensemble des détails, et vous réglez sur
                        place.
                    </li>
                </ol>
            </div>

            <div class="sr__col">
                <h3 class="sr__title">Paiement et annulation</h3>
                <p class="sr__prose">
                    <strong>Aucun argent ne transite par Vayla.</strong>
                    Nous ne prenons ni acompte, ni commission au voyageur, ni
                    empreinte de carte.
                </p>
                <p class="sr__prose">
                    Les conditions d'annulation se conviennent donc directement
                    avec le propriétaire, avant votre arrivée. Demandez-les :
                    un propriétaire sérieux vous les donnera par écrit.
                </p>
            </div>
        </div>
    </section>
</template>

<style scoped>
.sr__h2 {
    margin: 0 0 1.5rem;
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.032em;
    color: var(--ink);
}

.sr__cols {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem clamp(2rem, 4vw, 3rem);
}

.sr__title {
    margin: 0 0 .9rem;
    font-size: .74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: var(--text-3);
}

.sr__list {
    display: flex;
    flex-direction: column;
    gap: .55rem;
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: .88rem;
    color: var(--text-2);
}
.sr__list li { display: flex; align-items: flex-start; gap: .6rem; }
.sr__list :deep(.ai) { margin-top: .1rem; color: var(--text-3); }

.sr__steps {
    display: flex;
    flex-direction: column;
    gap: .8rem;
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: .88rem;
    line-height: 1.55;
    color: var(--text-2);
}
.sr__steps li { display: flex; align-items: flex-start; gap: .7rem; }

.sr__n {
    display: grid;
    place-items: center;
    flex: none;
    width: 1.4rem;
    height: 1.4rem;
    border-radius: 50%;
    background: var(--ink);
    color: var(--white);
    font-size: .72rem;
    font-weight: 700;
}

.sr__prose {
    margin: 0 0 .8rem;
    font-size: .88rem;
    line-height: 1.6;
    color: var(--text-2);
}
.sr__prose strong { color: var(--ink); }

@media (min-width: 860px) {
    .sr__cols { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
</style>
