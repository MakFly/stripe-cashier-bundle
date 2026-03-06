import { useEffect, useRef } from "react";
import { useMutation } from "@tanstack/react-query";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { CheckCircle2, Loader2 } from "lucide-react";
import { toast } from "sonner";
import { confirmCheckoutSession } from "../lib/api";
import { useAuthStore } from "../stores/auth";
import { Button } from "../components/ui/button";

export const CheckoutSuccessPage = () => {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const [searchParams] = useSearchParams();

  const orderId = Number(searchParams.get("orderId") ?? 0);
  const sessionId = searchParams.get("session_id") ?? "";
  const handledConfirmationRef = useRef<string | null>(null);

  const confirmMutation = useMutation({
    mutationFn: () => confirmCheckoutSession(orderId, sessionId),
    onSuccess: (order) => {
      toast.success("Paiement confirmé");
      const targetUserId = order.userId ?? user?.id;
      if (!targetUserId) {
        navigate("/orders", { replace: true, viewTransition: true });
        return;
      }
      navigate(`/orders/${targetUserId}/${order.id}`, {
        replace: true,
        viewTransition: true,
      });
    },
    onError: () => {
      toast.error("Impossible de confirmer le paiement");
    },
  });

  useEffect(() => {
    if (!user) {
      return;
    }
    if (orderId < 1 || sessionId === "") {
      return;
    }
    const confirmationKey = `${user.id}:${orderId}:${sessionId}`;
    if (handledConfirmationRef.current === confirmationKey) {
      return;
    }

    handledConfirmationRef.current = confirmationKey;
    confirmMutation.mutate();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [user, orderId, sessionId]);

  if (!user) {
    return (
      <div className="py-16 text-center space-y-4">
        <p className="text-muted-foreground">Connectez-vous pour finaliser la commande.</p>
        <Link to="/login?redirect=/orders" viewTransition>
          <Button>Se connecter</Button>
        </Link>
      </div>
    );
  }

  if (orderId < 1 || sessionId === "") {
    return (
      <div className="py-16 text-center space-y-4">
        <p className="text-destructive">Paramètres Stripe invalides.</p>
        <Link to="/orders" viewTransition>
          <Button>Voir mes commandes</Button>
        </Link>
      </div>
    );
  }

  return (
    <div className="py-16 text-center space-y-4">
      {confirmMutation.isError ? (
        <>
          <p className="text-destructive">Le paiement n'a pas pu être vérifié.</p>
          <Link to="/orders" viewTransition>
            <Button>Retour à mes commandes</Button>
          </Link>
        </>
      ) : (
        <>
          <CheckCircle2 className="h-10 w-10 text-green-600 mx-auto" />
          <p className="font-medium">Validation du paiement en cours...</p>
          <Loader2 className="h-5 w-5 animate-spin mx-auto text-muted-foreground" />
        </>
      )}
    </div>
  );
};
