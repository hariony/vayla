"""Le fond musical de la publicité propriétaires — composé ici, note par note.

**Aucun morceau trouvé en ligne** : une licence douteuse fait bloquer une
publicité, et une musique de banque se reconnaît. Celle-ci est synthétisée par
ce fichier ; elle appartient à Vayla.

Chaleureuse et posée, pas festive : on parle de confiance. 100 battements par
minute, une grille I–vi–IV–V en do majeur (Cmaj7, Am7, Fmaj7, G6), deux mesures
par accord. **Elle suit les plans** :

- 0–3 s   l'accroche : une nappe et quelques notes pincées, presque rien ;
- 3–8 s   le frein : la basse entre ;
- 8–15 s  la vérification : un shaker discret, le mouvement commence ;
- 14,6 s  un souffle qui monte vers le prix ;
- 15–21 s ce que ça coûte : la pulsation s'installe, une cloche sur « 0 Ar » (17,5 s) ;
- 21–27 s l'inscription : tout joue ;
- 27–30 s la signature : on s'arrête sur le premier accord, qui résonne.

Sortie : `sortie/musique-brute.wav` (48 kHz, stéréo). L'espace (écho, filtre,
limiteur) est ajouté ensuite par ffmpeg, dans `voix.mjs`.
"""
import numpy as np
import soundfile as sf

TAUX = 48000
DUREE = 30.0
BPM = 100
TEMPS = 60 / BPM
N = int(TAUX * DUREE)
t = np.arange(N) / TAUX
gauche = np.zeros(N)
droite = np.zeros(N)

rng = np.random.default_rng(7)  # même graine : deux rendus, la même musique


def hz(note):
    """Numéro MIDI → fréquence."""
    return 440.0 * 2 ** ((note - 69) / 12)


def poser(signal, debut, pan=0.0, gain=1.0):
    i = int(debut * TAUX)
    if i >= N:
        return
    fin = min(N, i + len(signal))
    s = signal[: fin - i] * gain
    gauche[i:fin] += s * (1 - max(0.0, pan))
    droite[i:fin] += s * (1 + min(0.0, pan))


def enveloppe(longueur, attaque, chute):
    n = int(longueur * TAUX)
    x = np.arange(n) / TAUX
    env = np.minimum(1.0, x / max(attaque, 1e-4)) * np.exp(-x / chute)
    return x, env


# ── La grille ────────────────────────────────────────────────────────────
ACCORDS = [
    [48, 52, 55, 59],  # Cmaj7
    [45, 48, 52, 55],  # Am7
    [41, 45, 48, 52],  # Fmaj7
    [43, 47, 50, 52],  # G6
]
MESURE = 4 * TEMPS
PAR_ACCORD = 2 * MESURE


def accord_a(instant):
    return ACCORDS[int(instant // PAR_ACCORD) % len(ACCORDS)]


# ── La nappe : sinus désaccordés, attaque lente ───────────────────────────
for k in range(int(np.ceil(DUREE / PAR_ACCORD)) + 1):
    debut = k * PAR_ACCORD
    if debut >= DUREE:
        break
    longueur = PAR_ACCORD + 1.2
    x = np.arange(int(longueur * TAUX)) / TAUX
    env = np.minimum(1, x / 0.9) * np.minimum(1, np.maximum(0, (longueur - x) / 1.2))
    for note in ACCORDS[k % 4]:
        f = hz(note + 12)
        for desaccord, pan in ((-0.12, -0.6), (0.12, 0.6)):
            onde = np.sin(2 * np.pi * (f + desaccord) * x) + 0.25 * np.sin(2 * np.pi * 2 * (f + desaccord) * x)
            poser(onde * env, debut, pan=pan, gain=0.018)

# ── Les notes pincées : un marimba doux, en croches ─────────────────────
MOTIF = [0, 2, 1, 3, 2, 1, 3, 2]  # indices dans l'accord, qui tournent
croche = TEMPS / 2
instant = 0.0
pas = 0
while instant < 27.0:
    if instant < 3.0 and pas % 2:  # l'accroche : une note sur deux
        instant += croche
        pas += 1
        continue
    note = accord_a(instant)[MOTIF[pas % len(MOTIF)]] + 24
    x, env = enveloppe(1.1, 0.004, 0.32)
    f = hz(note)
    onde = np.sin(2 * np.pi * f * x) + 0.35 * np.sin(2 * np.pi * 4 * f * x) * np.exp(-x / 0.05)
    humain = 1 + rng.uniform(-0.12, 0.12)
    poser(onde * env, instant + rng.uniform(0, 0.006), pan=0.35 if pas % 2 else -0.35, gain=0.055 * humain)
    instant += croche
    pas += 1

# ── La basse, à partir du frein ─────────────────────────────────────────
instant = round(3.0 / TEMPS) * TEMPS
while instant < 27.0:
    racine = accord_a(instant)[0] - 12
    x, env = enveloppe(TEMPS * 1.8, 0.01, 0.55)
    onde = np.sin(2 * np.pi * hz(racine) * x) + 0.15 * np.sin(2 * np.pi * 2 * hz(racine) * x)
    poser(onde * env, instant, gain=0.11)
    instant += 2 * TEMPS

# ── Le shaker, dès la vérification ──────────────────────────────────────
instant = round(8.0 / croche) * croche
while instant < 27.0:
    n = int(0.07 * TAUX)
    bruit = rng.standard_normal(n)
    bruit = np.diff(bruit, prepend=0)  # un souffle aigu, pas un grondement
    env = np.exp(-np.arange(n) / TAUX / 0.018)
    accent = 1.0 if int(round(instant / croche)) % 2 else 0.55
    poser(bruit * env, instant, pan=0.25, gain=0.012 * accent)
    instant += croche

# ── Le souffle qui monte vers le prix ────────────────────────────────────
n = int(1.1 * TAUX)
x = np.arange(n) / TAUX
souffle = np.diff(rng.standard_normal(n), prepend=0) * (x / x[-1]) ** 2.2
poser(souffle, 14.0, gain=0.02)

# ── La pulsation, à partir du prix ──────────────────────────────────────
instant = round(15.2 / TEMPS) * TEMPS
while instant < 27.0:
    x, env = enveloppe(0.35, 0.002, 0.09)
    glisse = 95 * np.exp(-x / 0.03) + 48
    onde = np.sin(2 * np.pi * np.cumsum(glisse) / TAUX)
    poser(onde * env, instant, gain=0.16)
    instant += TEMPS

# ── La cloche, quand le prix touche 0 Ar ────────────────────────────────
for note, retard in ((84, 0.0), (91, 0.03)):
    x, env = enveloppe(2.6, 0.002, 0.8)
    f = hz(note)
    onde = np.sin(2 * np.pi * f * x) + 0.4 * np.sin(2 * np.pi * 2.76 * f * x) * np.exp(-x / 0.25)
    poser(onde * env, 17.5 + retard, gain=0.05)

# ── La fin : le premier accord, tenu, qui résonne ───────────────────────
x = np.arange(int(3.2 * TAUX)) / TAUX
env = np.minimum(1, x / 0.05) * np.exp(-x / 1.4)
for note in ACCORDS[0] + [60, 64]:
    f = hz(note + 12)
    poser((np.sin(2 * np.pi * f * x) + 0.3 * np.sin(2 * np.pi * 4 * f * x) * np.exp(-x / 0.08)) * env, 27.0, gain=0.035)

# ── Sortie : fondu court en fin de piste, crêtes ramenées ───────────────
stereo = np.stack([gauche, droite], axis=1)
fondu = np.clip((DUREE - t) / 0.6, 0, 1)[:, None]
stereo = np.tanh(stereo * fondu * 1.4) / 1.4
stereo /= max(1e-9, np.max(np.abs(stereo))) / 0.9
sf.write("sortie/musique-brute.wav", stereo.astype(np.float32), TAUX)
print("musique-brute.wav : 30 s, 48 kHz, stéréo")
