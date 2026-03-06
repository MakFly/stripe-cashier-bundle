# Plan : Refonte Navbar — Split navbar + breadcrumb

## Context

La navbar actuelle (`components/custom-navbar.tsx`) est cassée et utilise un mélange de classes Nextra `x:*` et d'inline styles incohérents. Le composant `docs-controls.tsx` utilise 100% inline styles. L'objectif est de refaire une navbar clean, fonctionnelle sur toutes les routes, avec un design split 2 niveaux (style Tailwind docs / Prisma).

## Design retenu : Option C — Split navbar

```
┌─────────────────────────────────────────────────────┐
│ ◆ Cashier Symfony          [⌕ Search]  [FR|EN] [◐] │  ← Top bar (48px)
├─────────────────────────────────────────────────────┤
│ Docs  ·  GitHub  ·  Changelog       v1.0 stable    │  ← Sub bar (40px)
└─────────────────────────────────────────────────────┘
```

- **Top bar** : logo emerald + search input visible + locale switcher pills + theme toggle icon + GitHub icon
- **Sub bar** : liens navigation (Docs, GitHub) + version badge à droite
- **Mobile** : collapse en une seule barre + hamburger menu avec slide-down
- Fond : backdrop-blur glassmorphism, border subtle entre niveaux
- Couleur accent : emerald (`#10b981`) cohérent avec le reste du site

## Fichiers à modifier

| Fichier                        | Action                                                                                    |
| ------------------------------ | ----------------------------------------------------------------------------------------- |
| `components/custom-navbar.tsx` | **Réécriture complète** — Split navbar 2 niveaux                                          |
| `components/docs-controls.tsx` | **Refactor** — Remplacer inline styles par Tailwind, ajouter icônes SVG pour theme toggle |
| `app/globals.css`              | **Ajout** — Variables CSS pour la navbar, animations                                      |
| `app/[lang]/layout.tsx`        | **Ajustement mineur** — Mettre à jour les props navbar si nécessaire                      |

## Étapes d'implémentation

### 1. Mettre à jour `globals.css`

- Ajouter variables CSS navbar (`--navbar-height`, etc.)
- Ajouter transitions/animations pour les hover states

### 2. Refactorer `docs-controls.tsx`

- Remplacer tous les inline styles par des classes Tailwind
- Ajouter icônes SVG inline (sun/moon) pour le theme toggle au lieu du texte "Light"/"Dark"
- Garder la logique locale switcher existante (elle fonctionne)

### 3. Réécrire `custom-navbar.tsx`

- **Top bar** :
  - Logo : icône diamond SVG emerald + "Cashier Symfony" en font-semibold
  - Search : réutiliser le composant `search` de Nextra via `useThemeConfig()` mais le wrapper dans un input-like visible
  - Contrôles : `<DocsControls />` + GitHub icon
- **Sub bar** :
  - Liens : "Documentation" → `/{lang}/docs`, "GitHub" → repo externe
  - Badge version : pill `v1.x` style subtle à droite
- **Mobile** :
  - Cacher sub bar sur `max-md`
  - Hamburger → menu slide-down avec tous les liens
- Utiliser les classes Nextra `nextra-navbar` pour la compatibilité avec le thème
- Garder `setMenu`/`useMenu` de nextra-theme-docs pour le menu mobile

### 4. Ajuster le layout si nécessaire

- Vérifier que `navbar={<CustomNavbar lang={lang} />}` fonctionne toujours
- Ajuster `--nextra-navbar-height` si la hauteur change

## Contraintes

- **Pas de nouvelle dépendance** — Tailwind v4 + Nextra existants uniquement
- **i18n** — La navbar doit fonctionner identiquement sur `/fr/*` et `/en/*`
- **Homepage** — Les règles CSS `body:has([data-homepage])` cachent sidebar/toc/footer, la navbar reste visible
- **Search** — Réutiliser le composant Nextra search existant (pagefind)
- **Dark mode** — Supporter les 2 thèmes via les variables Nextra

## Vérification

1. Vérifier visuellement sur `/fr`, `/en`, `/fr/docs/introduction`, `/en/docs/installation`
2. Tester le responsive mobile (hamburger, collapse sub bar)
3. Tester le switch de locale (FR ↔ EN)
4. Tester le toggle theme dark/light
5. Vérifier que la search Nextra fonctionne
6. Vérifier que la sidebar docs reste fonctionnelle
