#!/usr/bin/env python3
"""Importe les intérieurs générés des annonces de démonstration.

Usage :

    python3 scripts/import-photos-ia.py <dossier> [--modele "Nom du modèle"]

Le dossier contient les fichiers nommés `<slug>-<piece>.png` (ou .jpg/.webp)
listés dans `docs/photos-annonces-ia.md`. Pour chacun, le script :

  1. refuse une image de moins de 1600 px de large — **jamais d'agrandissement**,
     c'est la règle qui a fait remplacer cinq fichiers flous du catalogue ;
  2. recadre en 4/3 centré, le seul rapport que connaissent la galerie et les
     cartes ;
  3. écrit `<clé>-800.webp`, `-1600.webp` et `-3200.webp` selon ce que la source
     permet, dans `laravel/public/images/lieux/` ;
  4. affiche les entrées PHP à coller dans `PhotoSeeder` et les tableaux
     `photos` de `ListingSeeder`.

Rien n'est écrit en base : le seeder reste la source de vérité, et vous relisez
ce qui va y entrer avant que ça y entre.

Dépendances : Pillow, cwebp (déjà utilisés pour les photographies de Commons).
"""

from __future__ import annotations

import argparse
import os
import subprocess
import sys
from pathlib import Path

try:
    from PIL import Image
except ImportError:  # pragma: no cover
    sys.exit("Pillow manquant : python3 -m pip install Pillow")

RACINE = Path(__file__).resolve().parent.parent
SORTIE = RACINE / "laravel" / "public" / "images" / "lieux"
PALIERS = [800, 1600, 3200]
EXTENSIONS = (".png", ".jpg", ".jpeg", ".webp")

# La pièce, sa légende. L'ordre du tableau est **l'ordre de la galerie** : la
# première est la couverture, celle qui porte l'annonce dans le catalogue.
ANNONCES: dict[str, list[tuple[str, str]]] = {
    "villa-ambatoloaka": [
        ("piscine", "La piscine à l'ombre des manguiers"),
        ("varangue", "La varangue couverte et sa table pour huit"),
        ("salon", "Le séjour ouvert sur la varangue"),
        ("chambre", "Une chambre sous moustiquaire"),
        ("exterieur", "La villa vue du jardin clos"),
        ("salle-eau", "Une des trois salles d'eau"),
    ],
    "bungalow-madirokely": [
        ("exterieur", "Le bungalow à quinze mètres de l'eau"),
        ("chambre", "La chambre et sa moustiquaire"),
        ("paillote", "La paillote du petit-déjeuner"),
        ("terrasse", "La terrasse et son hamac"),
        ("douche", "La douche extérieure"),
    ],
    "maison-itasy": [
        ("exterieur", "La maison sur la rive du lac"),
        ("ponton", "Le ponton privé et sa pirogue"),
        ("salon", "Le séjour et sa cheminée"),
        ("table", "La grande table pour dix"),
        ("chambre", "Une des quatre chambres"),
        ("cuisine", "La cuisine ouverte sur le jardin"),
    ],
    "front-de-mer-amborovy": [
        ("balcon", "Le balcon plein ouest au coucher du soleil"),
        ("salon", "Le séjour ouvert sur la cuisine"),
        ("chambre", "Une chambre climatisée"),
        ("cuisine", "La cuisine et son îlot"),
        ("salle-eau", "La salle de bain"),
    ],
    "villa-coloniale-antsirabe": [
        ("exterieur", "La façade des années trente"),
        ("salon", "Le salon et sa cheminée en pierre"),
        ("chambre", "Une des cinq chambres, chauffée"),
        ("cuisine", "La cuisine dimensionnée pour recevoir"),
        ("jardin", "Le jardin clos et ses jacarandas"),
        ("salle-eau", "Une salle de bain d'époque"),
    ],
    "case-ifaty": [
        ("recif", "Le récif à deux cents mètres"),
        ("exterieur", "La case posée sur le sable"),
        ("chambre", "L'intérieur, sous moustiquaire"),
        ("douche", "La douche extérieure"),
    ],
    "lodge-andasibe": [
        ("terrasse", "La terrasse sur la canopée"),
        ("exterieur", "Le lodge sur pilotis en lisière de forêt"),
        ("salon", "Le séjour en lambris de bois"),
        ("chambre", "Une des deux chambres"),
        ("salle-eau", "La salle d'eau"),
    ],
    "studio-thermal": [
        ("ensemble", "Le studio d'un seul regard"),
        ("bureau", "Le bureau face à la fenêtre"),
        ("cuisine", "La kitchenette complète"),
        ("salle-eau", "La salle d'eau"),
    ],
}


def php(valeur: str) -> str:
    return "'" + valeur.replace("\\", "\\\\").replace("'", "\\'") + "'"


def trouver(dossier: Path, base: str) -> Path | None:
    for ext in EXTENSIONS:
        chemin = dossier / f"{base}{ext}"
        if chemin.exists():
            return chemin
    return None


def convertir(source: Path, cle: str) -> int:
    """Recadre en 4/3 et écrit les paliers. Renvoie la plus grande largeur produite."""
    image = Image.open(source).convert("RGB")
    largeur, hauteur = image.size

    if largeur / hauteur > 4 / 3:
        cible = int(round(hauteur * 4 / 3))
        image = image.crop(((largeur - cible) // 2, 0, (largeur - cible) // 2 + cible, hauteur))
    else:
        cible = int(round(largeur * 3 / 4))
        image = image.crop((0, (hauteur - cible) // 2, largeur, (hauteur - cible) // 2 + cible))

    native = image.size[0]
    produites = []

    for palier in PALIERS:
        if palier > native and palier != PALIERS[0]:
            continue  # jamais d'agrandissement
        w = min(palier, native)
        redim = image.resize((w, int(round(w * 3 / 4))), Image.LANCZOS)
        temporaire = SORTIE / f".{cle}-{palier}.png"
        redim.save(temporaire)
        subprocess.run(
            ["cwebp", "-quiet", "-q", "68" if palier >= 3200 else "76", "-m", "6",
             "-sharp_yuv", str(temporaire), "-o", str(SORTIE / f"{cle}-{palier}.webp")],
            check=True,
        )
        os.remove(temporaire)
        produites.append(palier)

    return produites[-1]


def main() -> int:
    parseur = argparse.ArgumentParser(description=__doc__)
    parseur.add_argument("dossier", type=Path)
    parseur.add_argument("--modele", default="Image générée",
                         help="Nom du modèle, porté par le crédit (ex. « Flux 1.1 »)")
    args = parseur.parse_args()

    if not args.dossier.is_dir():
        return print(f"Dossier introuvable : {args.dossier}") or 1

    SORTIE.mkdir(parents=True, exist_ok=True)

    attendus = [(slug, piece, legende)
                for slug, pieces in ANNONCES.items()
                for piece, legende in pieces]

    manquants = [f"{slug}-{piece}" for slug, piece, _ in attendus
                 if trouver(args.dossier, f"{slug}-{piece}") is None]
    if manquants:
        print(f"⚠  {len(manquants)} fichier(s) manquant(s) — les autres seront tout de même "
              f"importés :\n   " + "\n   ".join(manquants) + "\n")

    entrees: list[str] = []
    galeries: dict[str, list[str]] = {}
    refuses: list[str] = []

    for slug, piece, legende in attendus:
        source = trouver(args.dossier, f"{slug}-{piece}")
        if source is None:
            continue

        with Image.open(source) as sonde:
            if sonde.size[0] < 1600:
                refuses.append(f"{source.name} — {sonde.size[0]} px, il en faut 1600")
                continue

        cle = f"ia-{slug}-{piece}"
        largeur = convertir(source, cle)
        galeries.setdefault(slug, []).append(cle)

        entrees.append("\n".join([
            "            [",
            f"                'key' => {php(cle)},",
            f"                'caption' => {php(legende)},",
            f"                'author' => {php(args.modele)},",
            "                'licence' => 'Image générée',",
            "                'licence_url' => null,",
            "                'source_url' => null,",
            f"                'width' => {largeur},",
            "                'is_ai' => true,",
            "            ],",
        ]))
        print(f"  ✓ {cle:44} {largeur} px")

    if refuses:
        print("\n⚠  Refusées (agrandir n'améliore rien, il faut les régénérer plus grandes) :")
        for ligne in refuses:
            print(f"   {ligne}")

    if not entrees:
        print("\nRien à importer.")
        return 1

    print(f"\n{len(entrees)} image(s) importée(s).\n")
    print("═" * 72)
    print("À coller à la fin du tableau de PhotoSeeder::photos() :\n")
    print("\n".join(entrees))
    print("\n" + "═" * 72)
    print("À poser dans les entrées correspondantes de ListingSeeder :\n")
    for slug, cles in galeries.items():
        liste = ", ".join(php(c) for c in cles)
        print(f"  // {slug}")
        print(f"  'photos' => [{liste}],\n")
    print("═" * 72)
    print("Puis : make seed && make cache")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
