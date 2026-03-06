import { Link, useLocation } from "react-router-dom";
import { ShoppingCart, User, LogOut, Package, Menu, X } from "lucide-react";
import { useState } from "react";
import { Button } from "../ui/button";
import { Badge } from "../ui/badge";
import { Skeleton } from "../ui/skeleton";
import { useAuthStore } from "../../stores/auth";
import { useCartStore } from "../../stores/cart";
import { cn } from "../../lib/utils";
import { CartSheet } from "./CartSheet";

export const Header = () => {
  const { user, logout, initialized } = useAuthStore();
  const items = useCartStore((state) => state.items);
  const guestItems = useCartStore((state) => state.guestItems);
  const totalItems =
    items.reduce((sum, item) => sum + item.quantity, 0) +
    guestItems.reduce((sum, item) => sum + item.quantity, 0);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [cartOpen, setCartOpen] = useState(false);
  const location = useLocation();
  const hasAuthHint = typeof document !== "undefined" && document.cookie.includes("auth_hint=1");

  const handleLogout = async () => {
    try {
      await logout();
      window.location.href = "/";
    } catch (error) {
      console.error("Logout failed:", error);
    }
  };

  const isActive = (path: string) => location.pathname === path;
  const getNavTarget = (path: string, requiresAuth?: boolean) => {
    if (!requiresAuth || user) return path;
    return `/login?redirect=${encodeURIComponent(path)}`;
  };

  const navLinks = [
    { path: "/", label: "Boutique" },
    { path: "/subscriptions", label: "Abonnement" },
    { path: "/orders", label: "Commandes", requiresAuth: true },
  ];

  return (
    <header className="sticky top-0 z-50 bg-background/80 backdrop-blur-md border-b border-border/50">
      <div className="container mx-auto">
        <div className="flex h-16 items-center justify-between px-4 lg:px-8">
          {/* Logo */}
          <Link
            to="/"
            viewTransition
            className="group flex items-center gap-3 focus-ring rounded-lg"
          >
            <div className="relative">
              <div className="w-10 h-10 rounded-xl bg-primary flex items-center justify-center transition-transform duration-300 group-hover:rotate-6">
                <Package className="h-5 w-5 text-primary-foreground" />
              </div>
              <div className="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-accent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
            </div>
            <div className="flex flex-col">
              <span
                className="text-xl font-semibold tracking-tight"
                style={{ fontFamily: "'Newsreader', serif" }}
              >
                SFCashier
              </span>
              <span className="text-[10px] uppercase tracking-widest text-muted-foreground -mt-1">
                Botanica
              </span>
            </div>
          </Link>

          {/* Desktop Navigation */}
          <nav className="hidden md:flex items-center gap-1">
            {navLinks.map((link) => {
              const target = getNavTarget(link.path, link.requiresAuth);
              return (
                <Link
                  key={link.path}
                  to={target}
                  viewTransition
                  className={cn(
                    "relative px-4 py-2 text-sm font-medium transition-colors rounded-lg focus-ring",
                    isActive(link.path)
                      ? "text-primary"
                      : "text-muted-foreground hover:text-foreground",
                  )}
                >
                  {link.label}
                  {isActive(link.path) && (
                    <span className="absolute bottom-0 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-primary" />
                  )}
                </Link>
              );
            })}
          </nav>

          {/* Actions */}
          <div className="flex items-center gap-2">
            {/* Cart Button */}
            <button
              onClick={() => setCartOpen(true)}
              className="relative group focus-ring rounded-xl"
            >
              <div
                className={cn(
                  "flex items-center gap-2 px-3 py-2 rounded-xl transition-all duration-300",
                  "hover:bg-secondary/80",
                )}
              >
                <ShoppingCart className="h-5 w-5" />
                <span className="hidden sm:inline text-sm font-medium">
                  Panier
                </span>
              </div>
              {totalItems > 0 && (
                <Badge
                  className={cn(
                    "absolute -right-2 -top-1 h-5 min-w-5 px-1.5 rounded-full",
                    "bg-accent text-accent-foreground border-2 border-background",
                    "animate-scale-in font-semibold text-xs",
                  )}
                >
                  {totalItems}
                </Badge>
              )}
            </button>
            <CartSheet open={cartOpen} onOpenChange={setCartOpen} />

            {/* User Section */}
            {!initialized ? (
              <div className="flex items-center gap-2">
                <div className="hidden sm:flex items-center gap-2 px-3 py-2 rounded-xl bg-secondary/50">
                  <Skeleton className="w-7 h-7 rounded-lg" />
                  <Skeleton className={cn("h-4", hasAuthHint ? "w-24" : "w-20")} />
                </div>
                <Skeleton className="h-9 w-9 rounded-xl" />
              </div>
            ) : user ? (
              <div className="flex items-center gap-2">
                <div className="hidden sm:flex items-center gap-2 px-3 py-2 rounded-xl bg-secondary/50">
                  <div className="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center">
                    <User className="h-4 w-4 text-primary" />
                  </div>
                  <span className="text-sm font-medium max-w-[120px] truncate">
                    {user.name}
                  </span>
                </div>
                <Button
                  variant="ghost"
                  size="icon"
                  onClick={handleLogout}
                  className="rounded-xl hover:bg-destructive/10 hover:text-destructive focus-ring"
                  title="Se déconnecter"
                >
                  <LogOut className="h-4 w-4" />
                </Button>
              </div>
            ) : (
              <Link to="/login" viewTransition>
                <Button
                  variant="default"
                  className="rounded-xl btn-press shadow-glow"
                >
                  <User className="h-4 w-4 mr-2" />
                  <span>Connexion</span>
                </Button>
              </Link>
            )}

            {/* Mobile Menu Toggle */}
            <button
              className="md:hidden p-2 rounded-xl hover:bg-secondary focus-ring"
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            >
              {mobileMenuOpen ? (
                <X className="h-5 w-5" />
              ) : (
                <Menu className="h-5 w-5" />
              )}
            </button>
          </div>
        </div>

        {/* Mobile Menu */}
        {mobileMenuOpen && (
          <div className="md:hidden border-t border-border/50 py-4 px-4 animate-fade-up">
            <nav className="flex flex-col gap-1">
              {navLinks.map((link) => {
                const target = getNavTarget(link.path, link.requiresAuth);
                return (
                  <Link
                    key={link.path}
                    to={target}
                    viewTransition
                    onClick={() => setMobileMenuOpen(false)}
                    className={cn(
                      "px-4 py-3 rounded-xl text-sm font-medium transition-colors focus-ring",
                      isActive(link.path)
                        ? "bg-primary text-primary-foreground"
                        : "hover:bg-secondary",
                    )}
                  >
                    {link.label}
                  </Link>
                );
              })}
            </nav>
          </div>
        )}
      </div>
    </header>
  );
};
