import { useEffect, useRef } from "react";
import { BrowserRouter, Routes, Route, useLocation } from "react-router-dom";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { Header } from "./components/layout/Header";
import { LoginPage } from "./pages/LoginPage";
import { TwoFactorSetupPage } from "./pages/TwoFactorSetupPage";
import { ProductsPage } from "./pages/ProductsPage";
import { ProductDetailPage } from "./pages/ProductDetailPage";
import { CartPage } from "./pages/CartPage";
import { CheckoutPage } from "./pages/CheckoutPage";
import { CheckoutSuccessPage } from "./pages/CheckoutSuccessPage";
import { OrdersPage } from "./pages/OrdersPage";
import { OrderDetailPage } from "./pages/OrderDetailPage";
import { MagicLinkVerifyPage } from "./pages/MagicLinkVerifyPage";
import { SubscriptionsPage } from "./pages/SubscriptionsPage";
import { ProtectedRoute } from "./components/auth/ProtectedRoute";
import { useAuthStore } from "./stores/auth";
import { useCartStore } from "./stores/cart";
import { Toaster } from "./components/ui/sonner";
import { Package, Heart, Github } from "lucide-react";

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
});

function AppContent() {
  const location = useLocation();
  const fetchUser = useAuthStore((state) => state.fetchUser);
  const fetchCart = useCartStore((state) => state.fetchCart);
  const fetchGuestCart = useCartStore((state) => state.fetchGuestCart);
  const user = useAuthStore((state) => state.user);
  const initialized = useAuthStore((state) => state.initialized);
  const fetchedUserRef = useRef(false);
  const lastFetchedUserIdRef = useRef<number | null>(null);
  const guestCartFetchedRef = useRef(false);

  useEffect(() => {
    if (fetchedUserRef.current) {
      return;
    }

    fetchedUserRef.current = true;
    void fetchUser();
  }, [fetchUser]);

  useEffect(() => {
    if (user) {
      if (lastFetchedUserIdRef.current === user.id) {
        return;
      }

      lastFetchedUserIdRef.current = user.id;
      guestCartFetchedRef.current = false;
      void fetchCart();
      return;
    }

    lastFetchedUserIdRef.current = null;
  }, [user, fetchCart]);

  useEffect(() => {
    if (initialized && !user) {
      if (guestCartFetchedRef.current) {
        return;
      }

      guestCartFetchedRef.current = true;
      void fetchGuestCart();
      return;
    }

    guestCartFetchedRef.current = false;
  }, [initialized, user, fetchGuestCart]);

  // Scroll to top on route change
  useEffect(() => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  }, [location.pathname]);

  return (
    <div className="min-h-screen flex flex-col">
      <Header />

      <main className="flex-1 container mx-auto px-4 lg:px-8 py-8">
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/auth/magic-link/verify" element={<MagicLinkVerifyPage />} />
          <Route
            path="/2fa-setup"
            element={
              <ProtectedRoute>
                <TwoFactorSetupPage />
              </ProtectedRoute>
            }
          />
          <Route path="/" element={<ProductsPage />} />
          <Route path="/subscriptions" element={<SubscriptionsPage />} />
          <Route path="/product/:slug" element={<ProductDetailPage />} />
          <Route path="/cart" element={<CartPage />} />
          <Route
            path="/checkout"
            element={
              <ProtectedRoute>
                <CheckoutPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/checkout/success"
            element={
              <ProtectedRoute>
                <CheckoutSuccessPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/orders"
            element={
              <ProtectedRoute>
                <OrdersPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/orders/:userId/:orderId"
            element={
              <ProtectedRoute>
                <OrderDetailPage />
              </ProtectedRoute>
            }
          />
        </Routes>
      </main>

      <Toaster position="bottom-center" />

      {/* Footer */}
      <footer className="mt-auto border-t border-border/50 bg-card/50 backdrop-blur-sm">
        <div className="container mx-auto px-4 lg:px-8 py-12">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {/* Brand */}
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-xl bg-primary flex items-center justify-center">
                  <Package className="h-5 w-5 text-primary-foreground" />
                </div>
                <div>
                  <span
                    className="text-lg font-semibold"
                    style={{ fontFamily: "'Newsreader', serif" }}
                  >
                    SFCashier
                  </span>
                  <span className="block text-[10px] uppercase tracking-widest text-muted-foreground -mt-0.5">
                    Botanica
                  </span>
                </div>
              </div>
              <p className="text-sm text-muted-foreground leading-relaxed">
                L'art du botanique à portée de main. Des produits d'exception
                pour sublimer votre quotidien.
              </p>
            </div>

            {/* Links */}
            <div className="space-y-4">
              <h4
                className="font-medium"
                style={{ fontFamily: "'Newsreader', serif" }}
              >
                Navigation
              </h4>
              <nav className="flex flex-col gap-2">
                {[
                  { label: "Boutique", path: "/" },
                  { label: "Abonnement", path: "/subscriptions" },
                  { label: "Mes commandes", path: "/orders" },
                  { label: "Mon panier", path: "/cart" },
                ].map((link) => (
                  <a
                    key={link.path}
                    href={link.path}
                    className="text-sm text-muted-foreground hover:text-foreground transition-colors link-underline w-fit"
                  >
                    {link.label}
                  </a>
                ))}
              </nav>
            </div>

            {/* Info */}
            <div className="space-y-4">
              <h4
                className="font-medium"
                style={{ fontFamily: "'Newsreader', serif" }}
              >
                À propos
              </h4>
              <div className="space-y-3">
                <p className="text-sm text-muted-foreground">
                  Une création de démonstration avec React, Symfony et beaucoup
                  d'amour.
                </p>
                <div className="flex items-center gap-4">
                  <a
                    href="#"
                    className="w-9 h-9 rounded-xl bg-secondary hover:bg-primary hover:text-primary-foreground flex items-center justify-center transition-all duration-300"
                  >
                    <Github className="h-4 w-4" />
                  </a>
                </div>
              </div>
            </div>
          </div>

          {/* Bottom */}
          <div className="mt-12 pt-8 border-t border-border/50 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p className="text-sm text-muted-foreground">
              © {new Date().getFullYear()} SFCashier. Tous droits réservés.
            </p>
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <span>Fait avec</span>
              <Heart className="h-4 w-4 text-accent fill-accent" />
              <span>et React</span>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <AppContent />
      </BrowserRouter>
    </QueryClientProvider>
  );
}

export default App;
