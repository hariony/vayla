/**
 * Pose la voix off française sur les vidéos rendues.
 *
 *   npm run voix                      → Kokoro, voix neuronale locale (par défaut)
 *   npm run voix -- mac               → la voix « Thomas » de macOS (maquette)
 *   npm run voix -- enregistrements   → voix/1.m4a … voix/6.m4a, enregistrés
 *   … -- sans-musique                 → la voix seule, sans le fond musical
 *
 * **Le fond musical est composé par `musique.py`**, pas trouvé en ligne : il
 * appartient à Vayla. Il passe sous la voix et **s'efface quand elle parle**
 * (compression déclenchée par la voix) : on doit comprendre chaque mot sur un
 * haut-parleur de téléphone.
 *
 * **Trois sources, un seul calage.**
 *
 * - **Kokoro** (Apache 2.0) tourne dans le conteneur `vayla-voix`, sans compte
 *   ni clé : voix française « siwis », entraînée sur le corpus SIWIS (CC BY 4.0).
 * - **`say`** ne sert que de maquette : la licence de macOS réserve ses voix
 *   système à un usage personnel et non commercial.
 * - **Un enregistrement** reste le meilleur choix pour la publicité réelle —
 *   surtout en malgache, qu'aucune de ces voix ne parle.
 *
 * **Les mots malgaches sont réécrits pour la voix, jamais pour l'écran.** Une
 * voix française lit « Vayla » « vé-la » et bute sur « ariary » : la phrase
 * prononcée porte une graphie phonétique, les sous-titres gardent la vraie.
 *
 * Chaque phrase a sa fenêtre : elle commence avec son plan et doit finir avant
 * le plan suivant. Une phrase trop longue est accélérée — jusqu'à un plafond au-
 * delà duquel elle ne se comprend plus, et le script le dit au lieu de la couper.
 */
import ffmpeg from 'ffmpeg-static'
import { execFileSync, spawnSync } from 'node:child_process'
import { existsSync, mkdirSync, rmSync, writeFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const ICI = dirname(fileURLToPath(import.meta.url))
const ARGS = process.argv.slice(2)
const SOURCE = ARGS.find((a) => ['mac', 'enregistrements'].includes(a)) ?? 'kokoro'
const MUSIQUE = ! ARGS.includes('sans-musique')
const VOIX = 'Thomas'

/** Ce que la voix doit dire, quand l'orthographe la tromperait. */
const PRONONCIATION = [
    [/\bVayla\b/g, 'Vaïla'],
    [/\bariary\b/g, 'a-riari'],
]
const prononcer = (texte) => PRONONCIATION.reduce((t, [motif, dit]) => t.replace(motif, dit), texte)

/** Au-delà de ce facteur, une voix accélérée ne sonne plus naturelle. */
const TEMPO_MAX = 1.18

/** [début, fin de la fenêtre, texte] — calés sur les plans de `composition.html`. */
const PHRASES = [
    [0.35, 2.95, 'Vous louez une villa ou un appartement meublé ?'],
    [3.4, 7.85, 'Les voyageurs hésitent à envoyer un acompte à un inconnu.'],
    [8.4, 14.85, 'Vayla vérifie votre logement, par un appel vidéo, depuis chez vous.'],
    [15.4, 20.85, "Zéro ariary pour l'inscrire et le faire vérifier. Une commission, seulement sur un séjour confirmé."],
    [21.3, 26.85, "Inscrivez-le avant l'ouverture. C'est gratuit, et sans mot de passe."],
    [27.25, 29.9, 'Vayla. Inscrivez votre logement.'],
]

const DEBIT = 185 // mots par minute, le débit posé d'une annonce
const DEBIT_MAX = 235

const tmp = join(ICI, 'sortie', 'voix-tmp')
rmSync(tmp, { recursive: true, force: true })
mkdirSync(tmp, { recursive: true })

const duree = (fichier) => {
    const r = spawnSync(ffmpeg, ['-i', fichier], { encoding: 'utf8' })
    const m = /Duration: (\d+):(\d+):([\d.]+)/.exec(r.stderr)
    return m ? Number(m[1]) * 3600 + Number(m[2]) * 60 + Number(m[3]) : 0
}

if (MUSIQUE) {
    execFileSync('docker', ['run', '--rm', '-v', `${ICI}:/travail`, 'vayla-voix', 'python', 'musique.py'], { stdio: 'inherit' })
}

let kokoro = null
if (SOURCE === 'kokoro') {
    writeFileSync(join(tmp, 'phrases.json'), JSON.stringify(PHRASES.map(([, , texte]) => prononcer(texte))))
    execFileSync('docker', ['run', '--rm', '-v', `${ICI}:/travail`, 'vayla-voix',
        'python', 'voix_kokoro.py', 'sortie/voix-tmp/phrases.json', 'sortie/voix-tmp', 'ff_siwis', '1.0'], { stdio: 'inherit' })
    kokoro = true
}

const pistes = PHRASES.map(([debut, fin, texte], i) => {
    const fenetre = fin - debut

    if (kokoro) {
        const fichier = join(tmp, `${i + 1}.wav`)
        const d = duree(fichier)
        // Trop long pour son plan : on accélère un peu, jamais au point de
        // presser la voix — au-delà, c'est la phrase qu'il faut raccourcir.
        const tempo = d > fenetre ? d / fenetre : 1
        if (tempo > TEMPO_MAX) {
            throw new Error(`Plan ${i + 1} : ${d.toFixed(2)} s pour ${fenetre.toFixed(2)} s — raccourcir « ${texte} ».`)
        }
        console.log(`plan ${i + 1} : ${d.toFixed(2)} s / ${fenetre.toFixed(2)} s${tempo > 1 ? ` (accéléré ×${tempo.toFixed(2)})` : ''}`)
        return { debut, fichier, tempo }
    }

    if (SOURCE === 'enregistrements') {
        const fichier = join(ICI, 'voix', `${i + 1}.m4a`)
        if (! existsSync(fichier)) throw new Error(`Enregistrement manquant : voix/${i + 1}.m4a`)
        const d = duree(fichier)
        if (d > fenetre) console.warn(`⚠ plan ${i + 1} : ${d.toFixed(2)} s pour ${fenetre.toFixed(2)} s disponibles`)
        return { debut, fichier }
    }

    for (let debit = DEBIT; ; debit += 10) {
        const fichier = join(tmp, `${i + 1}.aiff`)
        execFileSync('say', ['-v', VOIX, '-r', String(debit), '-o', fichier, prononcer(texte)])
        const d = duree(fichier)

        if (d <= fenetre) {
            console.log(`plan ${i + 1} : ${d.toFixed(2)} s / ${fenetre.toFixed(2)} s (${debit} mots/min)`)
            return { debut, fichier }
        }
        if (debit >= DEBIT_MAX) {
            throw new Error(`Plan ${i + 1} : « ${texte} » ne tient pas en ${fenetre.toFixed(2)} s même à ${DEBIT_MAX} mots/min. Raccourcir la phrase.`)
        }
    }
})

for (const format of ['9x16', '4x5']) {
    const video = join(ICI, 'sortie', `vayla-proprietaires-${format}.mp4`)
    if (! existsSync(video)) {
        console.warn(`(pas de ${format} : lancer d'abord « npm run rendu${format === '4x5' ? ' -- 4x5' : ''} »)`)
        continue
    }

    const entrees = pistes.flatMap((p) => ['-i', p.fichier])
    const m = pistes.length + 1 // l'index de la musique parmi les entrées
    if (MUSIQUE) entrees.push('-i', join(ICI, 'sortie', 'musique-brute.wav'))

    // Chaque phrase est décalée à son instant et mêlée en une piste de voix.
    let filtres = pistes.map((p, i) => `[${i + 1}:a]aresample=48000${p.tempo > 1 ? `,atempo=${p.tempo.toFixed(3)}` : ''},adelay=${Math.round(p.debut * 1000)}:all=1[a${i}]`).join(';')
        + ';' + pistes.map((_, i) => `[a${i}]`).join('') + `amix=inputs=${pistes.length}:normalize=0,apad,atrim=0:30,aformat=channel_layouts=stereo`

    if (MUSIQUE) {
        // La musique prend de l'espace (un écho court, les graves et les aigus
        // extrêmes retirés), puis **s'efface sous la voix** : la voix, dédoublée,
        // pilote la compression de la musique. Le tout est ramené à −16 LUFS.
        // **Chaque piste à son niveau avant le mélange** : la voix vers −17 LUFS,
        // la musique vers −29 — douze décibels d'écart, le rapport d'une
        // publicité parlée ; la compression en retire encore sous chaque phrase.
        filtres += ',loudnorm=I=-17:TP=-2:LRA=11[vx];[vx]asplit=2[v1][v2];'
            + `[${m}:a]highpass=f=70,lowpass=f=9500,aecho=0.8:0.5:110|230:0.22|0.12,loudnorm=I=-29:TP=-6:LRA=11[mus];`
            + '[mus][v2]sidechaincompress=threshold=0.015:ratio=7:attack=12:release=520:knee=4[fond];'
            + '[v1][fond]amix=inputs=2:normalize=0,loudnorm=I=-16:TP=-1.5:LRA=11[voix]'
    } else {
        filtres += ',loudnorm=I=-16:TP=-1.5:LRA=11[voix]'
    }

    const suffixe = { kokoro: 'voix', mac: 'voix-maquette', enregistrements: 'voix-enregistree' }[SOURCE] + (MUSIQUE ? '-musique' : '')
    const sortie = join(ICI, 'sortie', `vayla-proprietaires-${format}-${suffixe}.mp4`)
    const r = spawnSync(ffmpeg, [
        '-y', '-i', video, ...entrees,
        '-filter_complex', filtres,
        '-map', '0:v', '-map', '[voix]',
        '-c:v', 'copy', '-c:a', 'aac', '-b:a', '160k', '-ar', '48000',
        '-movflags', '+faststart', '-shortest', sortie,
    ], { encoding: 'utf8' })

    if (r.status !== 0) throw new Error(r.stderr.split('\n').slice(-6).join('\n'))
    console.log(`→ ${sortie}`)
}

rmSync(tmp, { recursive: true, force: true })
