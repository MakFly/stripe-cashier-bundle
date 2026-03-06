import { useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import { ArrowRight, Minus, Plus, ShoppingBag, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { updateCartItem } from "../lib/api";
import { useAuthStore } from "../stores/auth";
import { useCartStore } from "../stores/cart";
import { Button } from "../components/ui/button";
import { Card, CardContent } from "../components/ui/card";
import { formatPrice } from "../lib/utils";

export const CartPage = () => {
  const navigate = useNavigate();
  const { user, initialized } = useAuthStore();
  const {
    items,
    guestItems,
    isLoading,
    fetchCart,
    fetchGuestCart,
    removeItem,
    removeGuestItem,
    updateGuestQuantity,
    getTotal,
    getGuestTotal,
  } = useCartStore();

  useEffect(() => {
    if (user) {
      void fetchCart();
      return;
    }

    if (initialized) {
      void fetchGuestCart();
    }
  }, [user, initialized, fetchCart, fetchGuestCart]);

  const authenticated = Boolean(user);
  const empty = authenticated ? items.length === 0 : guestItems.length === 0;
  const total = authenticated ? getTotal() : getGuestTotal();

  const onAuthQtyChange = async (itemId: number, nextQty: number) => {
    if (nextQty < 1) return;

    try {
      await updateCartItem(itemId, nextQty);
      await fetchCart();
    } catch (e) {
      const message = e instanceof Error ? e.message : "Erreur mise à jour panier";
      toast.error(message);
    }
  };

  if (isLoading && empty) {
    return <div className="py-16 text-center text-muted-foreground">Chargement du panier...</div>;
  }

  if (empty) {
    return (
      <div className="flex flex-col justify-center items-center min-h-[60vh] gap-6 text-center">
        <div className="w-24 h-24 rounded-3xl bg-muted flex items-center justify-center">
          <ShoppingBag className="h-10 w-10 text-muted-foreground" />
        </div>
        <div>
          <h2 className="text-2xl font-medium mb-2">Votre panier est vide</h2>
          <p className="text-muted-foreground">Ajoutez des produits pour commencer votre commande.</p>
        </div>
        <Link to="/" viewTransition>
          <Button size="lg" className="rounded-xl">
            Découvrir la boutique
            <ArrowRight className="ml-2 h-4 w-4" />
          </Button>
        </Link>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-semibold">Panier</h1>

      <div className="space-y-3">
        {authenticated
          ? items.map((item) => (
              <Card key={item.id}>
                <CardContent className="p-4 flex items-center gap-4">
                  <div className="w-16 h-16 rounded-lg overflow-hidden bg-muted flex items-center justify-center">
                    {item.product.imageUrl ? (
                      <img src={item.product.imageUrl} alt={item.product.name} className="w-full h-full object-cover" />
                    ) : (
                      <span className="text-2xl">🌿</span>
                    )}
                  </div>

                  <div className="flex-1 min-w-0">
                    <p className="font-medium truncate">{item.product.name}</p>
                    <p className="text-sm text-muted-foreground">{formatPrice(item.product.price)}</p>
                  </div>

                  <div className="flex items-center gap-2">
                    <Button variant="outline" size="icon" onClick={() => onAuthQtyChange(item.id, item.quantity - 1)}>
                      <Minus className="h-4 w-4" />
                    </Button>
                    <span className="w-8 text-center">{item.quantity}</span>
                    <Button variant="outline" size="icon" onClick={() => onAuthQtyChange(item.id, item.quantity + 1)}>
                      <Plus className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="icon" onClick={() => void removeItem(item.id)}>
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </CardContent>
              </Card>
            ))
          : guestItems.map((item) => (
              <Card key={item.productId}>
                <CardContent className="p-4 flex items-center gap-4">
                  <div className="w-16 h-16 rounded-lg overflow-hidden bg-muted flex items-center justify-center">
                    {item.imageUrl ? (
                      <img src={item.imageUrl} alt={item.name} className="w-full h-full object-cover" />
                    ) : (
                      <span className="text-2xl">🌿</span>
                    )}
                  </div>

                  <div className="flex-1 min-w-0">
                    <p className="font-medium truncate">{item.name}</p>
                    <p className="text-sm text-muted-foreground">{formatPrice(item.price)}</p>
                  </div>

                  <div className="flex items-center gap-2">
                    <Button
                      variant="outline"
                      size="icon"
                      onClick={() => updateGuestQuantity(item.productId, Math.max(1, item.quantity - 1))}
                    >
                      <Minus className="h-4 w-4" />
                    </Button>
                    <span className="w-8 text-center">{item.quantity}</span>
                    <Button
                      variant="outline"
                      size="icon"
                      onClick={() => updateGuestQuantity(item.productId, item.quantity + 1)}
                    >
                      <Plus className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="icon" onClick={() => removeGuestItem(item.productId)}>
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </CardContent>
              </Card>
            ))}
      </div>

      <Card>
        <CardContent className="p-4 flex items-center justify-between">
          <span className="text-lg font-medium">Total</span>
          <span className="text-2xl font-bold text-primary">{formatPrice(total)}</span>
        </CardContent>
      </Card>

      <div className="flex justify-end gap-3">
        {!authenticated && (
          <Button variant="outline" onClick={() => navigate("/login?redirect=/checkout", { viewTransition: true })}>
            Se connecter
          </Button>
        )}
        <Button onClick={() => navigate(authenticated ? "/checkout" : "/login?redirect=/checkout", { viewTransition: true })}>
          Commander
        </Button>
      </div>
    </div>
  );
};
