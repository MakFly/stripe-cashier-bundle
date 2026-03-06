import { Link, useNavigate, useParams } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { ArrowLeft, Download, Package, ReceiptText } from "lucide-react";
import { ApiError, getOrderForUser } from "../lib/api";
import { useAuthStore } from "../stores/auth";
import { Button } from "../components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Badge } from "../components/ui/badge";
import { formatDate, formatPrice } from "../lib/utils";

export const OrderDetailPage = () => {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const params = useParams<{ userId: string; orderId: string }>();

  const userId = Number(params.userId ?? 0);
  const orderId = Number(params.orderId ?? 0);

  const { data: order, isLoading, error } = useQuery({
    queryKey: ["order-detail", userId, orderId],
    queryFn: () => getOrderForUser(userId, orderId),
    enabled: !!user && userId > 0 && orderId > 0,
  });

  if (!user) {
    return (
      <div className="py-16 text-center space-y-4">
        <p className="text-muted-foreground">Accès refusé à cette commande.</p>
        <Button onClick={() => navigate("/orders", { viewTransition: true })}>
          Retour aux commandes
        </Button>
      </div>
    );
  }

  if (isLoading) {
    return <div className="py-16 text-center text-muted-foreground">Chargement de la commande...</div>;
  }

  if (error instanceof ApiError && error.status === 403) {
    return (
      <div className="py-16 text-center space-y-4">
        <p className="text-muted-foreground">Accès refusé à cette commande.</p>
        <Button onClick={() => navigate("/orders", { viewTransition: true })}>
          Retour aux commandes
        </Button>
      </div>
    );
  }

  if (error || !order) {
    return (
      <div className="py-16 text-center space-y-4">
        <p className="text-destructive">Commande introuvable.</p>
        <Button onClick={() => navigate("/orders", { viewTransition: true })}>
          Retour aux commandes
        </Button>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      <Button variant="ghost" onClick={() => navigate("/orders", { viewTransition: true })}>
        <ArrowLeft className="h-4 w-4 mr-2" />
        Retour aux commandes
      </Button>

      <Card>
        <CardHeader>
          <div className="flex items-start justify-between gap-4">
            <div>
              <CardTitle>Détail commande {order.orderNumber}</CardTitle>
              <p className="text-sm text-muted-foreground mt-1">{formatDate(order.createdAt)}</p>
            </div>
            <Badge variant={order.status === "paid" ? "default" : "secondary"}>
              {order.status}
            </Badge>
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          {(order.items ?? []).map((item) => (
            <div key={item.id} className="flex items-center justify-between gap-4 border-b border-border pb-3 last:border-0">
              <div className="min-w-0">
                <p className="font-medium truncate">{item.productName}</p>
                <p className="text-sm text-muted-foreground">
                  {item.quantity} x {formatPrice(item.unitPrice)}
                </p>
              </div>
              <p className="font-semibold">{formatPrice(item.subtotal)}</p>
            </div>
          ))}

          <div className="pt-2 border-t border-border flex items-center justify-between">
            <span className="font-medium">Total</span>
            <span className="text-xl font-semibold">{formatPrice(order.total)}</span>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <ReceiptText className="h-5 w-5" />
            Facture
          </CardTitle>
        </CardHeader>
        <CardContent>
          {order.invoice ? (
            <div className="flex flex-col gap-4 rounded-2xl border border-border p-4 md:flex-row md:items-center md:justify-between">
              <div className="space-y-1">
                <p className="font-medium">{order.invoice.filename}</p>
                <p className="text-sm text-muted-foreground">
                  Générée le {formatDate(order.invoice.createdAt)}
                </p>
                <p className="text-sm text-muted-foreground">
                  {formatPrice(order.invoice.amountTotal)} · {order.invoice.status}
                </p>
              </div>
              <div className="flex flex-wrap gap-3">
                <a href={order.invoice.downloadPath}>
                  <Button variant="outline">
                    <Download className="mr-2 h-4 w-4" />
                    Télécharger
                  </Button>
                </a>
                {order.invoice.hostedInvoiceUrl ? (
                  <a
                    href={order.invoice.hostedInvoiceUrl}
                    target="_blank"
                    rel="noreferrer"
                  >
                    <Button>
                      Voir sur Stripe
                    </Button>
                  </a>
                ) : null}
              </div>
            </div>
          ) : (
            <div className="rounded-2xl border border-dashed border-border p-4 text-sm text-muted-foreground">
              Aucune facture n'est encore rattachée à cette commande.
            </div>
          )}
        </CardContent>
      </Card>

      <Link to="/" viewTransition>
        <Button variant="outline">
          <Package className="h-4 w-4 mr-2" />
          Continuer mes achats
        </Button>
      </Link>
    </div>
  );
};
