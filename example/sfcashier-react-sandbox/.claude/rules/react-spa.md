# React SPA Rules

## Stack
- React 19 + Vite 6
- shadcn/ui (style: new-york)
- Tailwind CSS 4
- React Router 7
- Zustand (state management)
- TanStack Query (data fetching)

## Structure
```
src/
├── components/
│   ├── ui/           # shadcn components
│   ├── layout/       # Header, Footer, Sidebar
│   └── forms/        # Form components
├── lib/
│   ├── api.ts        # API client
│   └── utils.ts      # cn() helper
├── pages/            # Route pages
├── stores/           # Zustand stores
└── types/            # TypeScript types
```

## Conventions

### Composants
- Functional components avec arrow functions
- Props interface nommée `ComponentNameProps`
- ForwardRef pour les composants UI

### Styling
- Utiliser `cn()` pour merge classes
- Tailwind utilities seulement
- Variables CSS pour les couleurs

### API Client
```typescript
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

export async function apiClient<T>(endpoint: string, options = {}): Promise<T> {
  const response = await fetch(`${API_URL}${endpoint}`, {
    ...options,
    headers: {
      'Content-Type': 'application/ld+json',
      Accept: 'application/ld+json',
    },
    credentials: 'include',
  });
  return response.json();
}
```

### State (Zustand)
```typescript
interface CartStore {
  items: CartItem[];
  addItem: (product: Product, quantity: number) => void;
  removeItem: (productId: number) => void;
  getTotal: () => number;
}

export const useCartStore = create<CartStore>((set, get) => ({
  items: [],
  addItem: (product, quantity) => set(state => ({
    items: [...state.items, { product, quantity }]
  })),
  // ...
}));
```

### Pages
- React Query pour data fetching
- Suspense pour loading states
- Error boundaries pour erreurs

### Types API Platform
```typescript
interface ApiCollection<T> {
  '@context': string;
  '@id': string;
  '@type': string;
  'hydra:member': T[];
  'hydra:totalItems': number;
}

interface Product {
  '@id': string;
  '@type': string;
  id: number;
  slug: string;
  name: string;
  price: number;
  imageUrl?: string;
}
```
