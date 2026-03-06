# Plan — Toast suppression panier + View Transitions

## Context

Deux corrections/améliorations indépendantes :

1. **CartSheet.tsx** appelle `removeItem`/`removeGuestItem` sans toast (contrairement à CartPage.tsx qui les a déjà)
2. Les transitions de navigation entre pages sont brutales (pas d'animation). React Router 7 + la View Transitions API du navigateur permettent des transitions fluides sans librairie supplémentaire.

---

## 1. Toast suppression — CartSheet.tsx

**Fichier** : `web/src/components/layout/CartSheet.tsx`

**Problème** : 4 appels sans toast (lignes ~119, 142, 193, 216). CartPage.tsx a déjà `toast.success("Article retiré")` — on harmonise.

**Changements** :

- Ajouter `import { toast } from 'sonner'`
- Sur le bouton trash (DB items, ligne ~142) : `onClick={() => { removeItem(item.id); toast.success("Article retiré"); }}`
- Sur le bouton minus quand quantity = 1 (DB items, ligne ~119) : `removeItem(item.id); toast.success("Article retiré");`
- Idem pour les deux cas guest (removeGuestItem)

---

## 2. View Transitions — React Router 7 natif

React Router 7.13.1 supporte nativement la prop `viewTransition` sur `<Link>` et l'option `{ viewTransition: true }` sur `navigate()`. Cela wrappe la mise à jour dans `document.startViewTransition()` pour activer la View Transitions API du navigateur.

### 2a. CSS transitions (index.css)

Ajouter dans `web/src/index.css` :

```css
/* ── View Transitions ────────────────────────────── */
::view-transition-old(root) {
  animation: vt-fade-out 260ms cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

::view-transition-new(root) {
  animation: vt-fade-in 300ms cubic-bezier(0, 0, 0.2, 1) forwards;
}

@keyframes vt-fade-out {
  from {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  to {
    opacity: 0;
    transform: translateY(-6px) scale(0.99);
  }
}

@keyframes vt-fade-in {
  from {
    opacity: 0;
    transform: translateY(10px) scale(0.995);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Respecter prefers-reduced-motion */
@media (prefers-reduced-motion: reduce) {
  ::view-transition-old(root),
  ::view-transition-new(root) {
    animation: none;
  }
}
```

### 2b. Prop `viewTransition` sur les `<Link>` — fichiers concernés

| Fichier                        | Lignes                                   | Changement               |
| ------------------------------ | ---------------------------------------- | ------------------------ |
| `components/layout/Header.tsx` | nav links + login link                   | Ajouter `viewTransition` |
| `pages/ProductsPage.tsx`       | ~128 (Link vers produit)                 | Ajouter `viewTransition` |
| `pages/CartPage.tsx`           | liens boutique, login, checkout, produit | Ajouter `viewTransition` |
| `pages/CartSheet.tsx`          | liens checkout et boutique               | Ajouter `viewTransition` |
| `pages/OrdersPage.tsx`         | ~92, ~168                                | Ajouter `viewTransition` |

### 2c. Option `{ viewTransition: true }` sur les `navigate()` — fichiers concernés

| Fichier                       | Appels                                                                           | Changement                                        |
| ----------------------------- | -------------------------------------------------------------------------------- | ------------------------------------------------- |
| `pages/ProductDetailPage.tsx` | `navigate(-1)`, `navigate("/")`                                                  | `navigate(-1, { viewTransition: true })`          |
| `pages/CartPage.tsx`          | `navigate('/checkout')`                                                          | `navigate('/checkout', { viewTransition: true })` |
| `pages/CheckoutPage.tsx`      | `navigate('/orders')`, `navigate('/login')`, `navigate('/cart')`, `navigate(-1)` | Ajouter `{ viewTransition: true }`                |
| `pages/LoginPage.tsx`         | `navigate('/')`                                                                  | `navigate('/', { viewTransition: true })`         |

---

## Fichiers modifiés (récap)

| Fichier                                   | Changement                              |
| ----------------------------------------- | --------------------------------------- |
| `web/src/index.css`                       | CSS `::view-transition-*`               |
| `web/src/components/layout/CartSheet.tsx` | Toast + viewTransition sur Links        |
| `web/src/components/layout/Header.tsx`    | `viewTransition` sur Links              |
| `web/src/pages/ProductsPage.tsx`          | `viewTransition` sur Link produit       |
| `web/src/pages/CartPage.tsx`              | `viewTransition` sur Links + navigate   |
| `web/src/pages/ProductDetailPage.tsx`     | `{ viewTransition: true }` sur navigate |
| `web/src/pages/CheckoutPage.tsx`          | `{ viewTransition: true }` sur navigate |
| `web/src/pages/LoginPage.tsx`             | `{ viewTransition: true }` sur navigate |
| `web/src/pages/OrdersPage.tsx`            | `viewTransition` sur Links              |

---

## Vérification

1. Retirer un article depuis le **CartSheet** (sidebar) → toast "Article retiré" apparaît en bas au centre
2. Cliquer sur un produit depuis la liste → transition fade + légère montée de la nouvelle page
3. Naviguer retour → idem
4. Tester dans les DevTools → `prefers-reduced-motion: reduce` désactive les animations
