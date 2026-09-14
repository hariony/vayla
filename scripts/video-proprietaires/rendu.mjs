/**
 * Rend la publicité propriétaires en MP4, image par image.
 *
 *   npm run rendu              → sortie/vayla-proprietaires-9x16.mp4 (Reels, Stories)
 *   npm run rendu -- 4x5       → sortie/vayla-proprietaires-4x5.mp4  (fil d'actualité)
 *
 * **Image par image, pas un enregistrement d'écran.** La chronologie GSAP de
 * `composition.html` est en pause ; on la positionne à chaque trentième de
 * seconde et on capture. Une animation enregistrée en temps réel saccade dès que
 * la machine ralentit ; ici le rendu est le même quelle que soit la machine.
 *
 * La photographie est copiée depuis `laravel/public/images/lieux` : c'est la
 * même que sur le site, avec le même crédit, écrit dans la vidéo.
 */
import { chromium } from 'playwright'
import ffmpeg from 'ffmpeg-static'
import { spawn } from 'node:child_process'
import { copyFileSync, mkdirSync, rmSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath, pathToFileURL } from 'node:url'

const ICI = dirname(fileURLToPath(import.meta.url))
const FORMAT = process.argv[2] === '4x5' ? '4x5' : '9x16'
const LARGEUR = 1080
const HAUTEUR = FORMAT === '4x5' ? 1350 : 1920
const IPS = 30

mkdirSync(join(ICI, 'images'), { recursive: true })
copyFileSync(
    join(ICI, '../../laravel/public/images/lieux/sainte-marie-crique-1600.webp'),
    join(ICI, 'images/sainte-marie-crique-1600.webp'),
)

const images = join(ICI, 'sortie', `images-${FORMAT}`)
rmSync(images, { recursive: true, force: true })
mkdirSync(images, { recursive: true })

const navigateur = await chromium.launch()
const page = await navigateur.newPage({ viewport: { width: LARGEUR, height: HAUTEUR }, deviceScaleFactor: 1 })
const url = pathToFileURL(join(ICI, 'composition.html')).href + (FORMAT === '4x5' ? '?format=4x5' : '')

await page.goto(url, { waitUntil: 'networkidle' })
await page.evaluate(() => window.pret)

const duree = await page.evaluate(() => window.DUREE)
const total = Math.round(duree * IPS)
const cadre = await page.$('#cadre')

for (let i = 0; i < total; i++) {
    await page.evaluate((t) => window.aller(t), i / IPS)
    await cadre.screenshot({ path: join(images, `${String(i).padStart(4, '0')}.png`) })
    if (i % 90 === 0) process.stdout.write(`\r${FORMAT} : image ${i}/${total}`)
}
process.stdout.write(`\r${FORMAT} : ${total} images capturées\n`)

await navigateur.close()

const sortie = join(ICI, 'sortie', `vayla-proprietaires-${FORMAT}.mp4`)

await new Promise((resolve, reject) => {
    // H.264, yuv420p et faststart : ce que Meta accepte sans réencoder à sa façon.
    const p = spawn(ffmpeg, [
        '-y', '-framerate', String(IPS), '-i', join(images, '%04d.png'),
        '-c:v', 'libx264', '-preset', 'slow', '-crf', '18', '-pix_fmt', 'yuv420p',
        '-movflags', '+faststart', sortie,
    ], { stdio: ['ignore', 'ignore', 'inherit'] })
    p.on('exit', (code) => (code === 0 ? resolve() : reject(new Error(`ffmpeg a échoué (${code})`))))
})

rmSync(images, { recursive: true, force: true })
console.log(`→ ${sortie}`)
