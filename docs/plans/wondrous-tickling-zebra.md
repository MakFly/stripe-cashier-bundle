# Plan — UI Nextra 4 : correspondre au design Next.js Docs

## Contexte

La documentation Cashier Symfony utilise Nextra 4 + `nextra-theme-docs`, mais l'UI ne ressemble pas
à la documentation officielle de Next.js. L'exploration révèle **3 causes racines** :

1. `globals.css` cible des classes CSS **Nextra v2/v3 obsolètes** (`.nextra-sidebar`, `.nextra-toc`, `.nextra-sidebar-footer`) qui n'existent plus en v4
2. `--nextra-content-width: 1440px` déclaré **deux fois** en conflit
3. `Layout` manque de props sidebar (`autoCollapse`, `defaultMenuCollapseLevel`) et `_meta.ts` est trop plat

---

## Schéma 1 — UI actuelle vs UI cible

```
UI ACTUELLE                          UI CIBLE (style next.js/docs)
┌────────────────────────────────┐   ┌────────────────────────────────────────────┐
│ Navbar: [Logo] [ThemeSwitch]   │   │ Navbar: [Logo]  [Search...]  [GitHub] [☀]  │
├────────────────────────────────┤   ├──────────┬─────────────────────┬───────────┤
│                                │   │ Sidebar  │                     │ TOC       │
│  (pas de sidebar visible)      │   │          │  # Titre            │ On page   │
│                                │   │ ▼ Guide  │                     │ ─ Section │
│  Contenu MDX pleine largeur    │   │   Intro  │  Contenu MDX        │ ─ Section │
│  1440px trop large             │   │   Install│  ~90rem max         │           │
│                                │   │ ▼ API    │                     │           │
│                                │   │   Stripe │                     │           │
│                                │   │   Webhook│                     │           │
└────────────────────────────────┘   └──────────┴─────────────────────┴───────────┘

Problèmes :
• Sidebar ne s'affiche pas → classes CSS obsolètes bloquent le rendu
• TOC absent ou mal positionné → idem
• Contenu trop large → double déclaration --nextra-content-width
```

---

## Schéma 2 — Pourquoi globals.css casse le thème

```
nextra-theme-docs/style.css           globals.css (ACTUEL — PROBLÈME)
        │                                      │
        ▼                                      ▼
Classes Nextra 4 :               Classes ciblées INEXISTANTES en v4 :
  .x\:bg-...                       .nextra-sidebar-footer  ← n'existe plus
  [data-nextra-sidebar]            .nextra-sidebar-container ← n'existe plus
  nextra-search-input              .nextra-sidebar           ← n'existe plus
  nextra-toc-content               .nextra-toc               ← n'existe plus

  ──────────────────────────────────────────────────────────
  Variables CSS Nextra 4 VALIDES :
    --nextra-primary-hue ✓         --nextra-content-width déclaré 2× ✗
    --nextra-bg ✓                  (ligne 10: 90rem, ligne 33: 1440px)
    --nextra-content-width ✓
  ──────────────────────────────────────────────────────────

Résultat : les overrides ne touchent rien → layout par défaut Nextra s'applique
           mais --nextra-content-width est corrompu par double déclaration
```

---

## Schéma 3 — Structure \_meta.ts cible (hiérarchique)

```
ACTUEL (plat)                        CIBLE (hiérarchique comme next.js docs)

app/_meta.ts                         app/_meta.ts
  docs → "Documentation"               docs → { type: "page", title: "Docs" }

app/docs/_meta.ts                    app/docs/_meta.ts
  introduction → "Introduction"        -- Guide de démarrage --   ← separator
  installation → "Installation"        introduction → "Introduction"
                                        installation → "Installation"
                                        -- Facturation --          ← separator
                                        subscriptions → "Abonnements"
                                        webhooks → "Webhooks"
                                        -- Référence API --        ← separator
                                        api → { type: "folder", ... }

                                     app/docs/api/_meta.ts
                                        entities → "Entities"
                                        services → "Services"
```

---

## Schéma 4 — Flux de données Layout Nextra 4

```
next.config.ts                    app/layout.tsx
┌─────────────────┐               ┌──────────────────────────────────────────┐
│ nextra({        │               │ getPageMap()  ←── scan app/**/_meta.ts   │
│   defaultShow   │──────────────▶│      │                                    │
│   CopyCode:true │               │      ▼                                    │
│ })              │               │ <Layout                                   │
└─────────────────┘               │   pageMap={pageMap}                       │
                                  │   sidebar={{ defaultMenuCollapseLevel:1   │
app/**/_meta.ts                   │              autoCollapse: true }}         │
┌─────────────────┐               │   toc={{ float:true, backToTop:true }}    │
│ introduction    │──────────────▶│ >                                         │
│ installation    │               │   {children}   ← page.mdx rendues         │
│ subscriptions   │               │ </Layout>                                 │
│ webhooks        │               └──────────────────────────────────────────┘
└─────────────────┘
                                  mdx-components.tsx
                                  ┌──────────────────────────────────────────┐
                                  │ useMDXComponents = getDocsMDXComponents  │
                                  │ → Callout, Tabs, Steps, Cards, Code...   │
                                  └──────────────────────────────────────────┘
```

---

## Fichiers à modifier

| Fichier             | Action         | Détail                                                                               |
| ------------------- | -------------- | ------------------------------------------------------------------------------------ |
| `app/globals.css`   | Nettoyer       | Supprimer toutes les classes Nextra v3 obsolètes, conserver uniquement variables CSS |
| `app/layout.tsx`    | Enrichir props | `sidebar.autoCollapse`, `sidebar.defaultMenuCollapseLevel: 1`                        |
| `app/docs/_meta.ts` | Restructurer   | Ajouter séparateurs + préparer les sections manquantes                               |

---

## Étapes d'implémentation

### 1. Nettoyer `globals.css`

- Supprimer `.nextra-sidebar-footer`, `.nextra-sidebar-container`, `.nextra-sidebar`, `.nextra-toc` (classes v3 obsolètes)
- Supprimer la double déclaration de `--nextra-content-width` (ligne 10 et ligne 33)
- Garder uniquement : variables `--nextra-primary-*`, `--nextra-bg`, `html { background }`, `::selection`

### 2. Enrichir `app/layout.tsx`

```tsx
<Layout
  sidebar={{
    defaultMenuCollapseLevel: 1,
    autoCollapse: true,
    defaultOpen: true,
    toggleButton: true,
  }}
  toc={{
    float: true,
    title: "Sur cette page",
    backToTop: "Retour en haut",
  }}
  navigation={true}
  editLink="Modifier cette page sur GitHub"
  ...
>
```

### 3. Restructurer `app/docs/_meta.ts`

```ts
export default {
  introduction: "Introduction",
  installation: "Installation",
  "--": { type: "separator", title: "Facturation" },
  subscriptions: "Abonnements",
  webhooks: "Webhooks",
};
```

> Note : les pages `subscriptions/` et `webhooks/` devront être créées (`page.mdx` minimal) pour que les entrées `_meta` soient valides.

---

## Vérification

1. `bun dev` → pas d'erreur console
2. `http://localhost:3000/docs/introduction` → sidebar visible avec sections hiérarchiques
3. TOC visible à droite sur les pages avec `##` headings
4. Sidebar s'auto-collapse sur mobile

---

## Sources

- [Layout Component | Nextra](https://nextra.site/docs/docs-theme/built-ins/layout)
- [Docs Theme | Nextra](https://nextra.site/docs/docs-theme/start)
- [Nextra 4 x App Router — The Guild](https://the-guild.dev/blog/nextra-4)
- [\_meta.js File | Nextra](https://nextra.site/docs/file-conventions/meta-file)
