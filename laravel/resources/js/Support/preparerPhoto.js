/**
 * Préparer une photo **avant** de l'envoyer : la réduire à ce que le serveur
 * gardera vraiment.
 *
 * Un appareil récent produit des fichiers de 10 à 25 Mo, et une connexion
 * mobile malgache envoie de l'ordre d'un mégaoctet toutes les quelques
 * secondes : une minute d'attente, souvent coupée avant la fin, pour des
 * pixels que le serveur jette aussitôt — il ne garde jamais plus de
 * 3 200 px de large, après un recadrage en 4/3. On envoie donc déjà cette
 * taille, en JPEG de très bonne qualité (0,92) : **quatre à six fois moins à
 * transférer pour une photo de 48 Mpx, et la même photo au bout**.
 *
 * - **Jamais en dessous de ce que le serveur produira** : la réduction vise la
 *   largeur *après recadrage* (`largeur`), pas celle du fichier. Une photo en
 *   hauteur garde donc sa largeur utile entière.
 * - **Jamais d'agrandissement, jamais de réencodage inutile** : une photo déjà
 *   à la bonne taille et légère part telle quelle — la compresser une fois de
 *   plus n'y gagnerait rien et y perdrait un peu.
 * - **L'orientation du téléphone est appliquée ici** (`imageOrientation:
 *   'from-image'`) : le fichier réduit n'a plus de données EXIF, il doit donc
 *   déjà être droit.
 * - **Au moindre doute, l'original part** : un navigateur qui ne sait pas
 *   décoder l'image (un format exotique, un téléphone à court de mémoire) ne
 *   doit pas empêcher l'envoi. Le serveur sait traiter l'original jusqu'à
 *   40 Mo et 80 mégapixels.
 *
 * @param {File} fichier
 * @param {{ largeur?: number, ratio?: number, qualite?: number }} [options]
 *   `largeur` : la largeur à garder après recadrage ; `ratio` : celui du
 *   recadrage (4/3 pour les photos, 1 pour un portrait).
 * @returns {Promise<File>}
 */
export async function preparerPhoto(fichier, { largeur = 3200, ratio = 4 / 3, qualite = 0.92 } = {}) {
    if (!fichier || !/^image\/(jpeg|png|webp)$/.test(fichier.type) || typeof createImageBitmap !== 'function') {
        return fichier
    }

    let image
    try {
        image = await createImageBitmap(fichier, { imageOrientation: 'from-image' })
    } catch {
        return fichier
    }

    try {
        const { width: l, height: h } = image
        // La largeur du recadrage que fera le serveur, et l'échelle qui la
        // ramène à `largeur` — jamais au-dessus de 1.
        const recadrage = Math.min(l, h * ratio)
        const echelle = Math.min(1, largeur / recadrage)

        const leger = fichier.size <= 3 * 1024 * 1024
        if (echelle === 1 && leger) return fichier

        const cible = { l: Math.round(l * echelle), h: Math.round(h * echelle) }
        const blob = await reduire(image, cible, qualite)

        // Un fichier réduit plus lourd que l'original (une photo déjà très
        // compressée) : on garde l'original.
        if (!blob || blob.size >= fichier.size) return fichier

        const nom = fichier.name.replace(/\.[^.]+$/, '') || 'photo'

        return new File([blob], `${nom}.jpg`, { type: 'image/jpeg', lastModified: Date.now() })
    } catch {
        return fichier
    } finally {
        image.close?.()
    }
}

/**
 * Réduit par moitiés successives, puis au pas final : un seul grand saut
 * (6 000 → 3 200) donne un résultat granuleux dans la plupart des
 * navigateurs, qui lissent sur quelques pixels seulement.
 */
async function reduire(image, { l, h }, qualite) {
    let source = image
    let sl = image.width
    let sh = image.height

    while (sl / 2 >= l * 1.5) {
        sl = Math.round(sl / 2)
        sh = Math.round(sh / 2)
        source = dessiner(source, sl, sh)
    }

    const toile = dessiner(source, l, h)

    if (toile.convertToBlob) return toile.convertToBlob({ type: 'image/jpeg', quality: qualite })

    return new Promise((resolve) => toile.toBlob(resolve, 'image/jpeg', qualite))
}

function dessiner(source, l, h) {
    const toile = typeof OffscreenCanvas === 'function' ? new OffscreenCanvas(l, h) : Object.assign(document.createElement('canvas'), { width: l, height: h })
    const ctx = toile.getContext('2d')
    // Un PNG transparent devient blanc, pas noir, en JPEG.
    ctx.fillStyle = '#fff'
    ctx.fillRect(0, 0, l, h)
    ctx.imageSmoothingEnabled = true
    ctx.imageSmoothingQuality = 'high'
    ctx.drawImage(source, 0, 0, l, h)

    return toile
}
