<script setup>
import { ref } from 'vue'

defineProps({
    appName: { type: String, default: 'CoreKit' },
    laravelVersion: { type: String, default: '13.x' },
    phpVersion: { type: String, default: '8.4' },
})

const features = [
    {
        title: 'Laravel 13',
        description: 'Backend moderne, typé, prêt pour la production avec Inertia côté serveur.',
        icon: 'server',
        accent: 'red',
    },
    {
        title: 'Vue 3 + Inertia',
        description: 'SPA sans API REST : tu écris du Laravel, tu réponds en composants Vue.',
        icon: 'sparkles',
        accent: 'green',
    },
    {
        title: 'Bootstrap 5',
        description: 'Design system éprouvé, responsive, customisable via Sass et tokens CSS.',
        icon: 'layout',
        accent: 'purple',
    },
    {
        title: 'Vite 8 + HMR',
        description: 'Build instantané, hot reload, dev expérience au top dans Docker.',
        icon: 'bolt',
        accent: 'amber',
    },
    {
        title: 'PostgreSQL 16',
        description: 'Base de données solide, migrations Laravel, seeders et factories prêts.',
        icon: 'database',
        accent: 'blue',
    },
    {
        title: 'Docker prêt',
        description: 'Stack complète : PHP-FPM + Nginx + Supervisor, multi-stage build optimisé.',
        icon: 'cube',
        accent: 'cyan',
    },
]

const stats = [
    { value: '13', label: 'Laravel' },
    { value: '8.4', label: 'PHP' },
    { value: '3', label: 'Vue' },
    { value: '16', label: 'Postgres' },
]

const copied = ref(false)
const installCmd = 'git clone https://github.com/your-org/corekit.git\ncd corekit && make install'

const copy = async () => {
    try {
        await navigator.clipboard.writeText(installCmd)
        copied.value = true
        setTimeout(() => (copied.value = false), 2000)
    } catch (_) {}
}
</script>

<template>
    <div class="ck-shell">
        <div class="ck-bg"></div>

        <header class="ck-nav">
            <div class="container d-flex align-items-center justify-content-between py-3">
                <a class="ck-brand" href="#">
                    <span class="ck-logo">⚡</span>
                    <span class="fw-bold">{{ appName }}</span>
                </a>

                <nav class="d-none d-md-flex gap-4 small">
                    <a href="#features" class="ck-link">Features</a>
                    <a href="#stack" class="ck-link">Stack</a>
                    <a href="#quickstart" class="ck-link">Quick start</a>
                </nav>

                <div class="d-flex gap-2">
                    <a href="https://laravel.com/docs" target="_blank" rel="noopener" class="btn btn-sm ck-btn-ghost d-none d-sm-inline-block">
                        Docs
                    </a>
                    <a href="https://github.com" target="_blank" rel="noopener" class="btn btn-sm ck-btn-light fw-semibold">
                        <span class="me-1">★</span> Star
                    </a>
                </div>
            </div>
        </header>

        <main class="container position-relative">
            <section class="ck-hero text-center">
                <span class="ck-pill mb-4">
                    <span class="ck-pulse"></span>
                    Laravel {{ laravelVersion }} · PHP {{ phpVersion }} · prêt à coder
                </span>

                <h1 class="ck-title">
                    Le starter kit
                    <span class="ck-gradient-text">full-stack</span>
                    pour livrer plus vite.
                </h1>

                <p class="ck-subtitle">
                    Backend Laravel, frontend Vue, base PostgreSQL, le tout dockerisé.
                    Tu clones, tu lances <code>make install</code>, tu codes.
                </p>

                <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
                    <a href="#quickstart" class="btn btn-lg fw-semibold ck-btn-primary">
                        Commencer
                        <span class="ms-2">→</span>
                    </a>
                    <a href="#features" class="btn btn-lg fw-semibold ck-btn-ghost">
                        Voir les features
                    </a>
                </div>

                <div class="ck-stats">
                    <div v-for="s in stats" :key="s.label" class="ck-stat">
                        <div class="ck-stat-value">{{ s.value }}</div>
                        <div class="ck-stat-label">{{ s.label }}</div>
                    </div>
                </div>
            </section>

            <section id="features" class="ck-section">
                <div class="text-center mb-5">
                    <span class="ck-eyebrow">Features</span>
                    <h2 class="ck-h2">Tout ce qu'il faut, rien de superflu.</h2>
                    <p class="ck-muted mb-0">
                        Une stack moderne, opinionnée, prête à scaler.
                    </p>
                </div>

                <div class="row g-4">
                    <div v-for="f in features" :key="f.title" class="col-md-6 col-lg-4">
                        <article class="ck-card h-100" :data-accent="f.accent">
                            <div class="ck-icon">
                                <svg v-if="f.icon === 'server'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="6" rx="2"/><rect x="3" y="14" width="18" height="6" rx="2"/><circle cx="7" cy="7" r="0.5" fill="currentColor"/><circle cx="7" cy="17" r="0.5" fill="currentColor"/></svg>
                                <svg v-else-if="f.icon === 'sparkles'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5z"/><path d="M19 14l1 2 2 1-2 1-1 2-1-2-2-1 2-1 1-2z"/></svg>
                                <svg v-else-if="f.icon === 'layout'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                                <svg v-else-if="f.icon === 'bolt'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L4 14h7l-2 8 9-12h-7l2-8z"/></svg>
                                <svg v-else-if="f.icon === 'database'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></svg>
                                <svg v-else-if="f.icon === 'cube'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.7l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.7l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.3 7 12 12 20.7 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>
                            </div>
                            <h3 class="ck-card-title">{{ f.title }}</h3>
                            <p class="ck-card-text">{{ f.description }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="quickstart" class="ck-section">
                <div class="row align-items-center g-5">
                    <div class="col-lg-5">
                        <span class="ck-eyebrow">Quick start</span>
                        <h2 class="ck-h2">Trois commandes, c'est tout.</h2>
                        <p class="ck-muted">
                            Pas de configuration manuelle. Le <code>Makefile</code> orchestre Docker,
                            Composer, npm, les migrations et le build des assets.
                        </p>
                        <ul class="ck-checklist">
                            <li>Réseau Docker créé automatiquement</li>
                            <li><code>.env</code> configuré pour PostgreSQL</li>
                            <li>Inertia, Vue 3, Bootstrap installés</li>
                            <li>Migrations exécutées, assets buildés</li>
                        </ul>
                    </div>

                    <div class="col-lg-7">
                        <div class="ck-terminal">
                            <div class="ck-terminal-bar">
                                <span class="ck-dot ck-dot--red"></span>
                                <span class="ck-dot ck-dot--amber"></span>
                                <span class="ck-dot ck-dot--green"></span>
                                <span class="ms-3 small ck-muted">~/projects/corekit</span>
                                <button class="ck-copy ms-auto" @click="copy">
                                    {{ copied ? '✔ copié' : 'copier' }}
                                </button>
                            </div>
                            <pre class="ck-terminal-body"><code><span class="ck-prompt">$</span> git clone https://github.com/your-org/corekit.git
<span class="ck-prompt">$</span> cd corekit
<span class="ck-prompt">$</span> make install

<span class="ck-out">▶ Building Docker image...</span>
<span class="ck-out">▶ Creating Laravel project (v13)...</span>
<span class="ck-out">▶ Installing Inertia, Vue 3, Bootstrap...</span>
<span class="ck-ok">✅ Installation complete!</span>
<span class="ck-out">   App: http://localhost:8040</span></code></pre>
                        </div>
                    </div>
                </div>
            </section>

            <section id="stack" class="ck-section text-center">
                <span class="ck-eyebrow">Stack</span>
                <h2 class="ck-h2">Construit avec ce qui marche.</h2>
                <div class="ck-stack">
                    <span class="ck-chip">Laravel 13</span>
                    <span class="ck-chip">Inertia.js</span>
                    <span class="ck-chip">Vue 3</span>
                    <span class="ck-chip">Bootstrap 5</span>
                    <span class="ck-chip">Vite 8</span>
                    <span class="ck-chip">PostgreSQL 16</span>
                    <span class="ck-chip">PHP 8.4</span>
                    <span class="ck-chip">Docker</span>
                    <span class="ck-chip">Nginx</span>
                    <span class="ck-chip">Supervisor</span>
                </div>
            </section>
        </main>

        <footer class="ck-footer">
            <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <span class="small ck-muted">
                    © {{ new Date().getFullYear() }} {{ appName }} — MIT License
                </span>
                <span class="small ck-muted">
                    Built with Laravel · Inertia · Vue · Bootstrap
                </span>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.ck-shell {
    position: relative;
    min-height: 100vh;
    color: #e6e7ee;
    background: #0b0c10;
    overflow-x: hidden;
}

.ck-bg {
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    background:
        radial-gradient(800px circle at 15% -10%, rgba(99, 102, 241, 0.25), transparent 40%),
        radial-gradient(700px circle at 90% 0%, rgba(236, 72, 153, 0.18), transparent 45%),
        radial-gradient(900px circle at 50% 100%, rgba(34, 197, 94, 0.12), transparent 50%),
        linear-gradient(180deg, #0b0c10 0%, #0d0f15 100%);
}

.ck-nav,
main,
.ck-footer {
    position: relative;
    z-index: 1;
}

.ck-muted { color: #a1a1aa !important; }

.ck-brand {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    color: #fff;
    text-decoration: none;
    font-size: 1.1rem;
}

.ck-logo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: linear-gradient(135deg, #6366f1, #ec4899);
    box-shadow: 0 8px 24px -8px rgba(99, 102, 241, 0.6);
    font-size: 1rem;
}

.ck-link {
    color: #a1a1aa;
    text-decoration: none;
    transition: color 0.2s;
}
.ck-link:hover { color: #fff; }

.ck-hero {
    padding: 6rem 0 4rem;
}

.ck-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.9rem;
    font-size: 0.85rem;
    color: #d4d4d8;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 999px;
    backdrop-filter: blur(8px);
}

.ck-pulse {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
    animation: ck-pulse 2s infinite;
}
@keyframes ck-pulse {
    70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
    100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}

.ck-title {
    font-size: clamp(2.4rem, 5vw, 4.2rem);
    font-weight: 800;
    line-height: 1.05;
    letter-spacing: -0.03em;
    margin: 0 auto 1.2rem;
    max-width: 18ch;
    color: #fff;
}

.ck-gradient-text {
    background: linear-gradient(135deg, #818cf8 0%, #ec4899 50%, #f59e0b 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

.ck-subtitle {
    font-size: 1.15rem;
    color: #a1a1aa;
    max-width: 38rem;
    margin: 0 auto;
}
.ck-subtitle code,
p code,
.ck-checklist code {
    color: #fbbf24;
    background: rgba(251, 191, 36, 0.1);
    padding: 0.1rem 0.4rem;
    border-radius: 6px;
    font-size: 0.95em;
}

.ck-btn-primary {
    background: #fff;
    color: #0b0c10 !important;
    border: none;
    border-radius: 12px;
    padding: 0.8rem 1.6rem;
    box-shadow: 0 12px 30px -10px rgba(255, 255, 255, 0.4);
    transition: transform 0.2s, box-shadow 0.2s;
}
.ck-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 40px -12px rgba(255, 255, 255, 0.5);
}

.ck-btn-ghost {
    color: #e6e7ee !important;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    padding: 0.8rem 1.6rem;
    transition: all 0.2s;
}
.ck-btn-ghost:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    color: #fff !important;
}

.ck-btn-light {
    background: #fff;
    color: #0b0c10 !important;
    border: none;
    border-radius: 8px;
    transition: transform 0.2s;
}
.ck-btn-light:hover { transform: translateY(-1px); }

.ck-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    max-width: 36rem;
    margin: 4rem auto 0;
}
.ck-stat {
    padding: 1.2rem 0.5rem;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
}
.ck-stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: -0.02em;
}
.ck-stat-label {
    font-size: 0.8rem;
    color: #71717a;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.ck-section {
    padding: 5rem 0;
}

.ck-eyebrow {
    display: inline-block;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: #818cf8;
    margin-bottom: 0.8rem;
}

.ck-h2 {
    font-size: clamp(1.8rem, 3.5vw, 2.6rem);
    font-weight: 700;
    color: #fff;
    letter-spacing: -0.02em;
    margin-bottom: 0.8rem;
}

.ck-card {
    position: relative;
    padding: 2rem;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    transition: transform 0.3s, border-color 0.3s, background 0.3s;
    overflow: hidden;
}
.ck-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(400px circle at 50% 0%, var(--accent, rgba(99, 102, 241, 0.15)), transparent 60%);
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
}
.ck-card:hover {
    transform: translateY(-4px);
    border-color: rgba(255, 255, 255, 0.15);
    background: rgba(255, 255, 255, 0.05);
}
.ck-card:hover::before { opacity: 1; }

.ck-card[data-accent="red"]    { --accent: rgba(239, 68, 68, 0.18);  }
.ck-card[data-accent="green"]  { --accent: rgba(34, 197, 94, 0.18);  }
.ck-card[data-accent="purple"] { --accent: rgba(168, 85, 247, 0.18); }
.ck-card[data-accent="amber"]  { --accent: rgba(245, 158, 11, 0.18); }
.ck-card[data-accent="blue"]   { --accent: rgba(59, 130, 246, 0.18); }
.ck-card[data-accent="cyan"]   { --accent: rgba(6, 182, 212, 0.18);  }

.ck-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.06);
    color: #c7d2fe;
    margin-bottom: 1.2rem;
    position: relative;
    z-index: 1;
}
.ck-icon svg { width: 22px; height: 22px; }

.ck-card[data-accent="red"]    .ck-icon { color: #fca5a5; }
.ck-card[data-accent="green"]  .ck-icon { color: #86efac; }
.ck-card[data-accent="purple"] .ck-icon { color: #d8b4fe; }
.ck-card[data-accent="amber"]  .ck-icon { color: #fcd34d; }
.ck-card[data-accent="blue"]   .ck-icon { color: #93c5fd; }
.ck-card[data-accent="cyan"]   .ck-icon { color: #67e8f9; }

.ck-card-title {
    font-size: 1.15rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: 0.4rem;
    position: relative;
    z-index: 1;
}

.ck-card-text {
    color: #a1a1aa;
    font-size: 0.95rem;
    line-height: 1.6;
    margin: 0;
    position: relative;
    z-index: 1;
}

.ck-checklist {
    list-style: none;
    padding: 0;
    margin: 1.5rem 0 0;
}
.ck-checklist li {
    position: relative;
    padding-left: 2rem;
    color: #d4d4d8;
    margin-bottom: 0.7rem;
    line-height: 1.6;
}
.ck-checklist li::before {
    content: '✓';
    position: absolute;
    left: 0;
    top: 2px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
    font-size: 0.8rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.ck-terminal {
    border-radius: 16px;
    background: linear-gradient(180deg, #1a1c23 0%, #14161c 100%);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 30px 80px -20px rgba(0, 0, 0, 0.6);
    overflow: hidden;
}

.ck-terminal-bar {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.7rem 1rem;
    background: rgba(0, 0, 0, 0.3);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.ck-dot {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}
.ck-dot--red    { background: #ef4444; }
.ck-dot--amber  { background: #f59e0b; }
.ck-dot--green  { background: #22c55e; }

.ck-copy {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #d4d4d8;
    font-size: 0.75rem;
    padding: 0.25rem 0.7rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}
.ck-copy:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }

.ck-terminal-body {
    margin: 0;
    padding: 1.4rem 1.6rem;
    font-family: 'SF Mono', Menlo, Consolas, monospace;
    font-size: 0.88rem;
    line-height: 1.7;
    color: #e6e7ee;
    white-space: pre-wrap;
}
.ck-prompt { color: #818cf8; user-select: none; }
.ck-out    { color: #93c5fd; }
.ck-ok     { color: #22c55e; font-weight: 600; }

.ck-stack {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.7rem;
    margin-top: 2rem;
}
.ck-chip {
    padding: 0.5rem 1rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #d4d4d8;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.ck-chip:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.2);
    color: #fff;
    transform: translateY(-2px);
}

.ck-footer {
    padding: 2rem 0;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    margin-top: 3rem;
}

@media (max-width: 576px) {
    .ck-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ck-hero  { padding: 3.5rem 0 2rem; }
}
</style>
