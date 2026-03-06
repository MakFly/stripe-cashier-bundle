# SFCashier Frontend

Frontend React SPA pour l'application SFCashier.

## Stack

- React 19
- Vite 7
- TypeScript
- Tailwind CSS 4
- shadcn/ui (New York style)
- React Router DOM
- TanStack Query
- Zustand
- Axios

## Structure

```
src/
├── components/
│   ├── layout/
│   │   └── Header.tsx       # Header avec navigation
│   └── ui/                  # Composants shadcn/ui
├── lib/
│   ├── api.ts               # Client API
│   └── utils.ts             # Utilitaires (cn, formatPrice, formatDate)
├── pages/
│   ├── LoginPage.tsx        # Page de connexion
│   ├── ProductsPage.tsx     # Liste des produits
│   ├── ProductDetailPage.tsx # Détail d'un produit
│   ├── CartPage.tsx         # Panier
│   ├── CheckoutPage.tsx     # Finalisation de commande
│   └── OrdersPage.tsx       # Historique des commandes
├── stores/
│   ├── auth.ts              # Store d'authentification (Zustand)
│   └── cart.ts              # Store du panier (Zustand)
├── types/
│   └── index.ts             # Types TypeScript
├── App.tsx                  # App avec Router + QueryProvider
└── main.tsx                 # Point d'entrée
```

## Installation

```bash
bun install
```

## Développement

```bash
bun run dev
```

Le serveur de développement démarre sur `http://localhost:5173`.

Les requêtes API vers `/api/*` sont proxées vers `http://localhost:8000`.

## Build

```bash
bun run build
```

## Preview

```bash
bun run preview
```

## Configuration

Variables d'environnement (voir `.env.example`):

- `VITE_API_URL`: URL de base de l'API (défaut: `/api/v1`)

## Routes

- `/` - Liste des produits
- `/product/:slug` - Détail d'un produit
- `/login` - Connexion
- `/cart` - Panier
- `/checkout` - Finalisation de commande
- `/orders` - Historique des commandes

## API

Le frontend communique avec l'API Symfony via:
- API Platform (format Hydra)
- Authentification par Bearer token
- Content-Type: `application/ld+json`
