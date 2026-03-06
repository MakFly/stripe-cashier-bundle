import { Link } from "react-router-dom";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { deleteAllOrders, getOrders } from "../lib/api";
import { useAuthStore } from "../stores/auth";
import { Button } from "../components/ui/button";
import { Card, CardContent } from "../components/ui/card";
import { Badge } from "../components/ui/badge";
import { formatPrice, formatDate } from "../lib/utils";
import { toast } from "sonner";
import {
  Package,
  ShoppingBag,
  ArrowRight,
  Sparkles,
  Clock,
  Truck,
  CheckCircle2,
  Trash2,
} from "lucide-react";
import { cn } from "../lib/utils";

const statusConfig: Record<
  string,
  {
    label: string;
    variant: "default" | "secondary" | "destructive" | "outline";
    icon: typeof Clock;
    color: string;
  }
> = {
  pending: {
    label: "En attente",
    variant: "secondary",
    icon: Clock,
    color: "text-amber-600",
  },
  paid: {
    label: "Payée",
    variant: "default",
    icon: CheckCircle2,
    color: "text-primary",
  },
  shipped: {
    label: "Expédié",
    variant: "default",
    icon: Truck,
    color: "text-primary",
  },
};

export const OrdersPage = () => {
  const { user } = useAuthStore();
  const queryClient = useQueryClient();

  const {
    data: ordersData,
    isLoading,
    error,
  } = useQuery({
    queryKey: ["orders"],
    queryFn: getOrders,
    enabled: !!user,
  });

  const deleteOrdersMutation = useMutation({
    mutationFn: deleteAllOrders,
    onSuccess: (result) => {
      queryClient.setQueryData(["orders"], {
        "@context": "/api/v1/contexts/Order",
        "@id": "/api/v1/orders",
        "@type": "hydra:Collection",
        member: [],
        "hydra:member": [],
        totalItems: 0,
        "hydra:totalItems": 0,
      });
      toast.success(
        `${result.deletedOrders} commande${result.deletedOrders > 1 ? "s" : ""} supprimée${result.deletedOrders > 1 ? "s" : ""}`,
      );
    },
    onError: () => {
      toast.error("Impossible de supprimer les commandes");
    },
  });

  if (!user) {
    return (
      <div className="flex flex-col justify-center items-center min-h-[60vh] gap-6">
        <div className="w-24 h-24 rounded-3xl bg-muted flex items-center justify-center animate-fade-up">
          <Package className="h-10 w-10 text-muted-foreground" />
        </div>
        <div className="text-center animate-fade-up stagger-1">
          <h2
            className="text-2xl font-medium mb-2"
            style={{ fontFamily: "'Newsreader', serif" }}
          >
            Connectez-vous
          </h2>
          <p className="text-muted-foreground mb-6">
            Vous devez être connecté pour voir vos commandes
          </p>
          <Link to="/login" viewTransition>
            <Button className="rounded-xl btn-press shadow-glow">
              Se connecter
              <ArrowRight className="ml-2 h-4 w-4" />
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  if (isLoading) {
    return (
      <div className="flex flex-col justify-center items-center min-h-[60vh] gap-4">
        <div className="relative">
          <div className="w-16 h-16 rounded-2xl bg-primary/10 animate-pulse-soft" />
          <Sparkles className="absolute inset-0 m-auto h-8 w-8 text-primary animate-float" />
        </div>
        <p className="text-muted-foreground animate-pulse-soft">
          Chargement des commandes...
        </p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex flex-col justify-center items-center min-h-[60vh] gap-4">
        <div className="w-20 h-20 rounded-2xl bg-destructive/10 flex items-center justify-center">
          <span className="text-3xl">🌱</span>
        </div>
        <p className="text-destructive font-medium">
          Erreur lors du chargement
        </p>
        <p className="text-muted-foreground text-sm">
          Veuillez rafraîchir la page
        </p>
      </div>
    );
  }

  const orders = ordersData?.member || [];
  const handleDeleteAllOrders = () => {
    if (orders.length === 0 || deleteOrdersMutation.isPending) {
      return;
    }

    const confirmed = window.confirm(
      "Supprimer toutes les commandes et les factures associées ?",
    );

    if (!confirmed) {
      return;
    }

    deleteOrdersMutation.mutate();
  };

  return (
    <div className="max-w-4xl mx-auto">
      {/* Header */}
      <div className="mb-8 animate-fade-up flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
          <h1
            className="text-3xl md:text-4xl font-medium mb-2"
            style={{ fontFamily: "'Newsreader', serif" }}
          >
            Mes Commandes
          </h1>
          <p className="text-muted-foreground">
            {orders.length === 0
              ? "Aucune commande pour le moment"
              : `${orders.length} commande${orders.length > 1 ? "s" : ""}`}
          </p>
        </div>
        {orders.length > 0 ? (
          <Button
            type="button"
            variant="destructive"
            className="rounded-xl"
            onClick={handleDeleteAllOrders}
            disabled={deleteOrdersMutation.isPending}
          >
            <Trash2 className="mr-2 h-4 w-4" />
            {deleteOrdersMutation.isPending ? "Suppression..." : "Tout supprimer"}
          </Button>
        ) : null}
      </div>

      {orders.length === 0 ? (
        /* Empty State */
        <div className="flex flex-col items-center justify-center py-16 px-4 animate-fade-up stagger-1">
          <div className="w-32 h-32 rounded-3xl bg-gradient-to-br from-primary/5 to-accent/5 flex items-center justify-center mb-8">
            <ShoppingBag className="h-14 w-14 text-primary/40" />
          </div>
          <h2
            className="text-xl font-medium mb-2"
            style={{ fontFamily: "'Newsreader', serif" }}
          >
            Aucune commande
          </h2>
          <p className="text-muted-foreground text-center max-w-sm mb-8">
            Vous n'avez pas encore passé de commande. Explorez notre boutique
            pour découvrir nos produits.
          </p>
          <Link to="/" viewTransition>
            <Button size="lg" className="rounded-xl btn-press shadow-glow">
              Découvrir la boutique
              <ArrowRight className="ml-2 h-4 w-4" />
            </Button>
          </Link>
        </div>
      ) : (
        /* Orders List */
        <div className="space-y-4">
          {orders.map((order) => {
            const config = statusConfig[order.status] || statusConfig.pending;
            const StatusIcon = config.icon;

            return (
              <Card key={order.id} className="overflow-hidden border-0 shadow-soft card-lift">
                <CardContent className="p-0">
                  <Link to={`/orders/${order.userId ?? user.id}/${order.id}`} viewTransition className="block">
                    <div className="flex flex-col sm:flex-row sm:items-center gap-4 p-5">
                    {/* Status Icon */}
                    <div
                      className={cn(
                        "w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0",
                        order.status === "paid" ? "bg-success/10" : "bg-primary/10",
                      )}
                    >
                      <StatusIcon
                        className={cn(
                          "h-6 w-6",
                          config.color,
                          order.status === "pending" && "animate-spin",
                        )}
                      />
                    </div>

                    {/* Order Info */}
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-3 mb-1">
                        <span className="font-mono text-sm font-semibold">
                          {order.orderNumber}
                        </span>
                        <Badge
                          variant={config.variant}
                          className="rounded-lg text-xs"
                        >
                          {config.label}
                        </Badge>
                      </div>
                      <div className="text-sm text-muted-foreground">
                        {formatDate(order.createdAt)}
                      </div>
                    </div>

                    {/* Total */}
                    <div className="text-right">
                      <div
                        className="text-xl font-semibold text-primary"
                        style={{ fontFamily: "'Newsreader', serif" }}
                      >
                        {formatPrice(order.total)}
                      </div>
                    </div>
                    </div>
                  </Link>
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}
    </div>
  );
};
