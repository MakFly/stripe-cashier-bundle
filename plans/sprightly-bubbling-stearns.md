# Plan : i18n FR/EN + Fix Recherche Pagefind

## Context

Le projet `docs/` est un site de documentation Next.js 15 + Nextra 4.6.1 (App Router).
Deux problèmes à résoudre :

1. **Recherche cassée** : `TypeError: failed to fetch dynamically imported module` — `pagefind` n'est pas installé et aucun `postbuild` ne génère l'index `/_pagefind/`.
2. **Pas de système i18n** : le site est mono-langue FR, les labels UI sont hardcodés en français, aucune infra de traduction n'existe.

---

## Partie 1 — Fix Recherche Pagefind

### Cause

`pagefind` est absent des `node_modules` et le script `build` ne génère pas l'index de recherche.
Le composant Nextra tente de charger `/_pagefind/pagefind.js` dynamiquement → crash.

### Fichiers modifiés

- `docs/package.json`

### Changements

**`package.json`** : ajouter `pagefind` en devDependency + script `postbuild` :

```json
"scripts": {
  "dev": "next dev",
  "build": "next build",
  "postbuild": "pagefind --site .next/server/app --output-path public/_pagefind",
  "start": "next start",
  "lint": "eslint"
},
"devDependencies": {
  "pagefind": "latest",
  ...
}
```

**`.gitignore`** (à la racine de `docs/`) : ajouter `public/_pagefind/`

### Installation

```bash
cd docs/
bun add -D pagefind
```

---

## Partie 2 — i18n FR + EN (routing complet)

### Architecture Nextra v4 i18n

Nextra v4 utilise :

- `nextConfig.i18n` pour déclarer les locales → passe `NEXTRA_LOCALES` au build
- `nextra/locales` → fonction `proxy` pour middleware de détection auto
- `getPageMap('/fr')` / `getPageMap('/en')` → page maps par locale
- Prop `i18n` sur le composant `<Layout>` de `nextra-theme-docs` → sélecteur de langue dans la navbar

### Nouvelle structure de fichiers

```
docs/
├── app/
│   ├── layout.tsx                      MODIFIÉ  (lang dynamique via params ou suppressHydrationWarning)
│   ├── _meta.ts                        MODIFIÉ  (pointer vers fr/ et en/)
│   ├── fr/                             NOUVEAU  (tout le contenu FR actuel déplacé ici)
│   │   ├── page.tsx                    DÉPLACÉ  depuis app/page.tsx
│   │   └── docs/
│   │       ├── layout.tsx              DÉPLACÉ  depuis app/docs/layout.tsx (+ i18n prop)
│   │       ├── _meta.ts                DÉPLACÉ  depuis app/docs/_meta.ts
│   │       └── [section]/page.mdx     DÉPLACÉ  (16 sections)
│   └── en/                             NOUVEAU  (stubs EN à traduire)
│       ├── page.tsx                    NOUVEAU  (version EN de la home)
│       └── docs/
│           ├── layout.tsx              NOUVEAU  (même layout, labels EN)
│           ├── _meta.ts                NOUVEAU  (labels EN)
│           └── [section]/page.mdx     NOUVEAU  (stubs EN ou contenu traduit)
├── middleware.ts                       NOUVEAU  (locale detection/redirect)
├── next.config.ts                      MODIFIÉ  (i18n config)
└── package.json                        MODIFIÉ  (pagefind + postbuild)
```

### Étapes d'implémentation

#### 1. `next.config.ts` — Ajouter i18n

```typescript
const nextConfig: NextConfig = {
  i18n: {
    locales: ["fr", "en"],
    defaultLocale: "fr",
  },
  transpilePackages: ["geist"],
  eslint: { ignoreDuringBuilds: true },
  outputFileTracingRoot: __dirname,
  async redirects() {
    return [
      { source: "/docs", destination: "/docs/introduction", permanent: false },
      {
        source: "/fr/docs",
        destination: "/fr/docs/introduction",
        permanent: false,
      },
      {
        source: "/en/docs",
        destination: "/en/docs/introduction",
        permanent: false,
      },
    ];
  },
};
```

#### 2. `middleware.ts` (racine de `docs/`)

```typescript
export { proxy as middleware } from "nextra/locales";

export const config = {
  matcher: ["/((?!api|_next/static|_next/image|favicon.ico|_pagefind).*)"],
};
```

#### 3. Déplacer `app/page.tsx` → `app/fr/page.tsx`

Créer `app/en/page.tsx` avec contenu EN (peut être une version simplifiée au début).

#### 4. Déplacer `app/docs/` → `app/fr/docs/`

Conserver exactement les mêmes fichiers MDX et `_meta.ts`.

#### 5. `app/fr/docs/layout.tsx` — Mettre à jour

```typescript
import { Layout, Navbar, ThemeSwitch } from "nextra-theme-docs";
import { getPageMap } from "nextra/page-map";
import "nextra-theme-docs/style.css";

export default async function FrDocsLayout({ children }: { children: React.ReactNode }) {
  return (
    <Layout
      navbar={<Navbar logo={<b>Cashier Symfony</b>} projectLink="..."><ThemeSwitch /></Navbar>}
      pageMap={await getPageMap('/fr')}
      i18n={[
        { locale: 'fr', name: 'Français' },
        { locale: 'en', name: 'English' },
      ]}
      toc={{ float: true, title: "Sur cette page", backToTop: "Retour en haut" }}
      // ... rest of props
    >
      {children}
    </Layout>
  );
}
```

#### 6. `app/en/docs/layout.tsx` — Nouveau

Même structure avec labels EN :

```typescript
toc={{ float: true, title: "On this page", backToTop: "Back to top" }}
// getPageMap('/en')
// i18n prop identique
```

#### 7. `app/_meta.ts` — Mettre à jour

```typescript
const meta = {
  index: { display: "hidden" },
  fr: { title: "Documentation", type: "page", display: "hidden" },
  en: { title: "Documentation", type: "page", display: "hidden" },
};
```

#### 8. `app/layout.tsx` — Rendre le `lang` dynamique

Le root layout doit accepter un `lang` param via le `[lang]` segment. Sinon, garder `lang="fr"` et laisser le middleware gérer la redirection.

#### 9. Créer les stubs EN

Pour chaque section (16 pages), créer `app/en/docs/[section]/page.mdx` avec le contenu EN (peut pointer vers la version FR le temps de traduire, ou être un stub minimal).

#### 10. `app/en/docs/_meta.ts`

Même structure que FR mais labels en anglais :

```typescript
const meta = {
  introduction: "Introduction",
  installation: "Installation",
  "-- getting-started": { type: "separator", title: "Getting Started" },
  configuration: "Configuration",
  // ...
};
```

---

## Vérification

### Recherche

```bash
cd docs/
bun run build
# Vérifier que public/_pagefind/ est créé
ls public/_pagefind/
bun run start
# Tester la recherche sur http://localhost:3000/fr/docs/introduction
```

### i18n

1. Ouvrir `http://localhost:3000` → doit rediriger vers `/fr/` ou `/en/` selon la langue du navigateur
2. Naviguer vers `/fr/docs/introduction` → contenu FR + sélecteur de langue visible
3. Naviguer vers `/en/docs/introduction` → contenu EN + sélecteur de langue visible
4. Cliquer sur le sélecteur de langue → bascule entre FR et EN

---

## Fichiers critiques

| Fichier               | Action                            |
| --------------------- | --------------------------------- |
| `docs/next.config.ts` | Ajouter `i18n` config             |
| `docs/middleware.ts`  | Créer (nouveau)                   |
| `docs/package.json`   | Ajouter `pagefind` + `postbuild`  |
| `docs/app/_meta.ts`   | Mettre à jour                     |
| `docs/app/layout.tsx` | Adapter pour i18n                 |
| `docs/app/fr/`        | Créer (déplacer contenu existant) |
| `docs/app/en/`        | Créer (nouveau contenu EN)        |

## Utilitaires réutilisables

- `nextra/locales` → `proxy` function (middleware prêt à l'emploi)
- `nextra/page-map` → `getPageMap(locale)` (déjà utilisé dans layout)
- `nextra-theme-docs` → prop `i18n` sur `<Layout>` (sélecteur natif)
