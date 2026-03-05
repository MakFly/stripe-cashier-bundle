# Plan : Répliquer l'UI Next.js Docs (correction sidebar + redesign)

## Contexte

La documentation CashierBundle utilise Nextra v4 + nextra-theme-docs v4.6.1 (Next.js 15, React 19).
Deux problèmes bloquants persistent après la tentative précédente :

1. **Sidebar et TOC invisibles** — root cause identifiée : `globals.css` passait `--nextra-bg: #0a0a0a` (hex) au lieu du triplet RGB attendu. Nextra calcule `rgb(var(--nextra-bg))` — avec un hex la valeur CSS est invalide, le fond du sidebar est transparent et invisible sur fond sombre.
2. **Design pas Next.js-like** — gradients sur titres, accent violet Stripe, police qui ne s'applique pas à Nextra car override au mauvais niveau CSS.

**Objectif** : reproduire exactement l'UI de nextjs.org/docs — sidebar fonctionnel, TOC visible, Geist font partout, accent `#0070f3`, fond `#0a0a0a`, zéro décoration superflue.

---

## Diagnostic technique Nextra v4

| Variable              | Format attendu                                             | Erreur actuelle                      |
| --------------------- | ---------------------------------------------------------- | ------------------------------------ |
| `--nextra-bg`         | triplet RGB `10 10 10`                                     | hex `#0a0a0a` → `rgb()` invalide     |
| `--x-font-sans`       | doit être surchargé dans `:root` AVANT les layers Tailwind | était absent                         |
| `body { background }` | géré par Nextra via `bg-nextra-bg`                         | override conflictuel dans layout.tsx |

**Sélecteur dark Nextra v4** : `:where(.dark *)` — actif car `<html className="dark">` est hardcodé ✓

---

## Fichiers à modifier

| Fichier                   | Type de changement                         |
| ------------------------- | ------------------------------------------ |
| `docs/app/globals.css`    | Refonte section overrides Nextra           |
| `docs/app/layout.tsx`     | Supprimer `body style={{ background }}`    |
| `docs/mdx-components.tsx` | Ajuster `pre` fond (`#161616` → `#111111`) |

**Ne pas toucher** : `_meta.global.tsx` (déjà `type: 'page'`), `_meta.ts` files, `next.config.mjs`, classes `.cb-*`

---

## Implémentation détaillée

### 1. `docs/app/globals.css` — section overrides Nextra

**Remplacer** les blocs `html { ... }`, `html.dark { ... }`, `body { ... }`, `.nextra-content { ... }` par :

```css
/* === NEXTRA : Override police (doit être dans :root, avant les layers Tailwind de Nextra) === */
:root {
  --x-font-sans: "Geist", system-ui, sans-serif;
  --x-font-mono: "Geist Mono", ui-monospace, monospace;
}

/* === NEXTRA : Variables de thème === */
html {
  /* Fallback light (non utilisé — on est toujours en dark) */
  --nextra-bg: 255 255 255;
  --nextra-primary-hue: 212;
  --nextra-primary-saturation: 100%;
  --nextra-primary-lightness: 48%;
  -webkit-font-smoothing: antialiased;
}

html.dark {
  /* CRITIQUE : triplet RGB, pas hex — utilisé comme rgb(var(--nextra-bg)) */
  --nextra-bg: 10 10 10; /* → #0a0a0a, fond Next.js docs */
  --nextra-primary-hue: 212;
  --nextra-primary-saturation: 100%;
  --nextra-primary-lightness: 48%; /* → #0070f3 bleu Vercel */
  --shiki-color-text: #ededed;
  --shiki-color-background: transparent;
}
```

**Supprimer** :

- `html { font-family: ...; background: ... }` → Nextra gère
- `body { background: ...; color: ... }` → Nextra gère
- `.nextra-content { font-family: ... }` → géré par `--x-font-sans`

**Garder intact** : toutes les classes `.cb-*`, `.mdx-link`, scrollbar, animations, responsive.

### 2. `docs/app/layout.tsx`

```diff
- <body style={{ background: "#0a0a0f" }}>
+ <body>
```

Nextra applique le fond via ses propres classes Tailwind qui lisent `--nextra-bg`.

### 3. `docs/mdx-components.tsx`

Un seul ajustement (minor) :

```diff
  pre: ... style={{
-   background: '#161616',
+   background: '#111111',
  }}
```

Tout le reste (`h1-h4`, `p`, `a`, `code`, `blockquote`, `table`, `hr`) est déjà correct.

---

## Pourquoi ça va marcher

1. `--nextra-bg: 10 10 10` → `rgb(10, 10, 10)` = valide → sidebar/navbar ont leur fond
2. `--x-font-sans` dans `:root` → surcharge le `@layer theme` de Nextra → Geist appliqué à tout le layout
3. `--nextra-primary-*` → bleu Vercel pour liens actifs sidebar, accents
4. Suppression de l'override `body background` → Nextra contrôle le fond, pas de conflit
5. `_meta.global.tsx` avec `type: 'page'` → normalizePages inclut les pages file-system dans `flatDocsDirectories` → sidebar généré

---

## Vérification

1. `cd docs && bun run dev`
2. `http://localhost:3000/docs/installation` — vérifier :
   - **Sidebar gauche** visible : Installation / Abonnements / Webhooks / API Reference
   - **TOC droite** visible avec ancres de la page
   - **Font Geist** partout (sidebar, navbar, contenu)
   - **Fond** : `#0a0a0a` uniforme, pas de fond blanc/gris parasite
   - **Accent bleu** : liens actifs sidebar, liens dans le contenu
   - **Titres** : `#ededed`, sans gradient, sans barre colorée
3. `http://localhost:3000` — homepage `.cb-*` classes intactes
