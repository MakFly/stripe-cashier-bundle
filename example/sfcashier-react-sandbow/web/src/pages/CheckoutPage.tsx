import { useEffect } from "react";
import { useMutation } from "@tanstack/react-query";
import { Link, useNavigate } from "react-router-dom";
import { ArrowLeft, CreditCard, Loader2 } from "lucide-react";
import { toast } from "sonner";
import { createCheckoutSession } from "../lib/api";
import { useAuthStore } from "../stores/auth";
import { useCartStore } from "../stores/cart";
import { Button } from "../components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { formatPrice } from "../lib/utils";

export const CheckoutPage = () => {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const { items, fetchCart, clearItems, isLoading } = useCartStore();

  useEffect(() => {
    if (user) {
      void fetchCart();
    }
  }, [user, fetchCart]);

  const total = items.reduce((sum, item) => sum + item.product.price * item.quantity, 0);

  const orderMutation = useMutation({
    mutationFn: createCheckoutSession,
    onSuccess: (data) => {
      clearItems();
      if (!data.checkoutUrl) {
        toast.error("Lien de paiement Stripe introuvable");
        return;
      }
      window.location.href = data.checkoutUrl;
    },
    onError: (e) => {
      const message = e instanceof Error ? e.message : "Impossible de démarrer le paiement";
      toast.error(message);
    },
  });

  if (items.length === 0) {
    return (
      <div className="py-16 text-center space-y-4">
        <p className="text-muted-foreground">Votre panier est vide.</p>
        <Link to="/" viewTransition>
          <Button>Retour boutique</Button>
        </Link>
      </div>
    );
  }

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <Button variant="ghost" onClick={() => navigate("/cart", { viewTransition: true })}>
        <ArrowLeft className="h-4 w-4 mr-2" />
        Retour panier
      </Button>

      <Card>
        <CardHeader>
          <CardTitle>Récapitulatif</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {items.map((item) => (
            <div key={item.id} className="flex items-center justify-between">
              <span>
                {item.product.name} x {item.quantity}
              </span>
              <span className="font-medium">{formatPrice(item.product.price * item.quantity)}</span>
            </div>
          ))}

          <div className="border-t pt-3 flex items-center justify-between text-lg font-semibold">
            <span>Total</span>
            <span>{formatPrice(total)}</span>
          </div>
        </CardContent>
      </Card>

      <Button
        onClick={() => orderMutation.mutate()}
        disabled={orderMutation.isPending || isLoading}
        className="w-full rounded-xl"
        size="lg"
      >
        {orderMutation.isPending ? (
          <>
            <Loader2 className="h-4 w-4 mr-2 animate-spin" />
            Redirection vers Stripe...
          </>
        ) : (
          <>
            <CreditCard className="h-4 w-4 mr-2" />
            Payer et commander
          </>
        )}
      </Button>
    </div>
  );
};
