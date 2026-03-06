import { create } from "zustand";
import type {
  CartItem,
  GuestCartItem,
  GuestCartPayloadItem,
  Product,
} from "../types";
import {
  getCart as apiGetCart,
  addToCart as apiAddToCart,
  removeFromCart as apiRemoveFromCart,
  getGuestCart as apiGetGuestCart,
  saveGuestCart as apiSaveGuestCart,
  clearGuestCartCookie as apiClearGuestCartCookie,
  mergeGuestCart as apiMergeGuestCart,
  syncCart,
} from "../lib/api";

let fetchCartInFlight: Promise<void> | null = null;
let fetchGuestCartInFlight: Promise<void> | null = null;

interface CartState {
  items: CartItem[];
  guestItems: GuestCartItem[];
  isLoading: boolean;

  fetchCart: () => Promise<void>;
  fetchGuestCart: () => Promise<void>;
  addItem: (
    product: Product,
    quantity: number,
    isAuthenticated: boolean,
  ) => Promise<void>;
  removeItem: (itemId: number) => Promise<void>;
  removeGuestItem: (productId: number) => void;
  updateQuantity: (itemId: number, quantity: number) => void;
  updateGuestQuantity: (productId: number, quantity: number) => void;
  clearItems: () => void;
  clearGuestCart: () => void;
  mergeGuestCartToUser: () => Promise<void>;
  syncGuestCart: () => Promise<void>;
  getTotal: () => number;
  getGuestTotal: () => number;
  getTotalCount: () => number;
}

const toGuestPayload = (items: GuestCartItem[]): GuestCartPayloadItem[] =>
  items.map((item) => ({
    productId: item.productId,
    quantity: item.quantity,
  }));

export const useCartStore = create<CartState>((set, get) => ({
  items: [],
  guestItems: [],
  isLoading: false,

  fetchCart: async () => {
    if (fetchCartInFlight) {
      await fetchCartInFlight;
      return;
    }

    fetchCartInFlight = (async () => {
      set({ isLoading: true });
      try {
        const cart = await apiGetCart();
        set({ items: cart.items, isLoading: false });
      } catch {
        set({ isLoading: false });
      } finally {
        fetchCartInFlight = null;
      }
    })();

    await fetchCartInFlight;
  },

  fetchGuestCart: async () => {
    if (fetchGuestCartInFlight) {
      await fetchGuestCartInFlight;
      return;
    }

    fetchGuestCartInFlight = (async () => {
      try {
        const guestCart = await apiGetGuestCart();
        set({ guestItems: guestCart.items });
      } catch {
        // silencieux: ne pas bloquer l'UI si la récupération échoue
      } finally {
        fetchGuestCartInFlight = null;
      }
    })();

    await fetchGuestCartInFlight;
  },

  addItem: async (
    product: Product,
    quantity: number,
    isAuthenticated: boolean,
  ) => {
    if (!isAuthenticated) {
      const { guestItems } = get();
      const existing = guestItems.find((item) => item.productId === product.id);
      const nextGuestItems = existing
        ? guestItems.map((item) =>
            item.productId === product.id
              ? { ...item, quantity: item.quantity + quantity }
              : item,
          )
        : [
            ...guestItems,
            {
              productId: product.id,
              productIri: product["@id"],
              quantity,
              name: product.name,
              slug: product.slug,
              price: product.price,
              imageUrl: product.imageUrl,
              stock: product.stock,
              description: product.description,
            },
          ];

      set({ guestItems: nextGuestItems });

      try {
        const persisted = await apiSaveGuestCart(toGuestPayload(nextGuestItems));
        set({ guestItems: persisted.items });
      } catch {
        // silencieux: le panier reste en mémoire si la persistance échoue
      }
      return;
    }

    set({ isLoading: true });
    try {
      const newItem = await apiAddToCart(product["@id"], quantity);
      const items = get().items;

      // Upsert robuste: l'API peut renvoyer un IRI produit différent (id vs slug),
      // donc on privilégie l'id de CartItem puis l'id produit.
      const existingIndexByItemId = items.findIndex((item) => item.id === newItem.id);
      if (existingIndexByItemId >= 0) {
        const nextItems = [...items];
        nextItems[existingIndexByItemId] = newItem;
        set({ items: nextItems, isLoading: false });
        return;
      }

      const existingIndexByProductId = items.findIndex(
        (item) => item.product.id === newItem.product.id,
      );
      if (existingIndexByProductId >= 0) {
        const nextItems = [...items];
        nextItems[existingIndexByProductId] = newItem;
        set({ items: nextItems, isLoading: false });
        return;
      }

      set({ items: [...items, newItem], isLoading: false });
    } catch (error) {
      set({ isLoading: false });
      throw error;
    }
  },

  removeItem: async (itemId: number) => {
    set({ isLoading: true });
    try {
      await apiRemoveFromCart(itemId);
      set({
        items: get().items.filter((item) => item.id !== itemId),
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false });
      throw error;
    }
  },

  removeGuestItem: (productId: number) => {
    const nextGuestItems = get().guestItems.filter(
      (item) => item.productId !== productId,
    );
    set({ guestItems: nextGuestItems });
    void apiSaveGuestCart(toGuestPayload(nextGuestItems))
      .then((persisted) => set({ guestItems: persisted.items }))
      .catch(() => {});
  },

  updateQuantity: (itemId: number, quantity: number) => {
    set({
      items: get().items.map((item) =>
        item.id === itemId ? { ...item, quantity } : item,
      ),
    });
  },

  updateGuestQuantity: (productId: number, quantity: number) => {
    const nextGuestItems = get().guestItems.map((item) =>
      item.productId === productId ? { ...item, quantity } : item,
    );
    set({ guestItems: nextGuestItems });
    void apiSaveGuestCart(toGuestPayload(nextGuestItems))
      .then((persisted) => set({ guestItems: persisted.items }))
      .catch(() => {});
  },

  clearItems: () => set({ items: [] }),

  clearGuestCart: () => {
    set({ guestItems: [] });
    void apiClearGuestCartCookie().catch(() => {});
  },

  mergeGuestCartToUser: async () => {
    const cart = await apiMergeGuestCart();
    set({ items: cart.items, guestItems: [] });
  },

  syncGuestCart: async () => {
    const { guestItems } = get();
    if (guestItems.length === 0) {
      return;
    }

    try {
      const items = guestItems.map((item) => ({
        productId: item.productId,
        quantity: item.quantity,
      }));
      const cart = await syncCart({ items });
      set({ items: cart.items, guestItems: [] });
      await apiClearGuestCartCookie().catch(() => {});
    } catch {
      // silencieux — ne pas bloquer le login
    }
  },

  getTotal: () =>
    get().items.reduce(
      (total, item) => total + item.product.price * item.quantity,
      0,
    ),

  getGuestTotal: () =>
    get().guestItems.reduce(
      (total, item) => total + item.price * item.quantity,
      0,
    ),

  getTotalCount: () => {
    const { items, guestItems } = get();
    return (
      items.reduce((sum, item) => sum + item.quantity, 0) +
      guestItems.reduce((sum, item) => sum + item.quantity, 0)
    );
  },
}));
