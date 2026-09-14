"""Dit chaque phrase de la voix off avec Kokoro, un fichier WAV par plan.

Lancé par `voix.mjs` dans le conteneur `vayla-voix` : phrases.json en entrée,
voix-tmp/N.wav en sortie. Les mots malgaches et la marque sont **réécrits pour
être prononcés**, jamais affichés ainsi : « Vaïla », pas « Vayla » lu à la
française.
"""
import json, sys
import soundfile as sf
from kokoro_onnx import Kokoro

entree, dossier, voix, vitesse = sys.argv[1], sys.argv[2], sys.argv[3], float(sys.argv[4])
kokoro = Kokoro("modeles/kokoro-v1.0.onnx", "modeles/voices-v1.0.bin")

for i, texte in enumerate(json.load(open(entree)), start=1):
    audio, taux = kokoro.create(texte, voice=voix, speed=vitesse, lang="fr-fr")
    sf.write(f"{dossier}/{i}.wav", audio, taux)
    print(f"{i}.wav {len(audio) / taux:.2f}s", flush=True)
