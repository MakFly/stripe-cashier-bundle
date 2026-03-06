import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Link, useNavigate, useParams } from "react-router-dom";
import { ArrowLeft, Minus, Plus, ShoppingCart } from "lucide-react";
import { toast } from "sonner";
import { getProduct } from "../lib/api";
import { useAuthStore } from "../stores/auth";
import { useCartStore } from "../stores/cart";
import { Badge } from "../components/ui/badge";
import { Button } from "../components/ui/button";
import { Card, CardContent } from "../components/ui/card";
import { formatPrice } from "../lib/utils";

export const ProductDetailPage = () => {
  const { slug } = useParams<{ slug: string }>();
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const { addItem, isLoading: isCartLoading } = useCartStore();
  const [quantity, setQuantity] = useState(1);

  const {
    data: product,
    isLoading,
    error,
  } = useQuery({
    queryKey: ["product", slug],
    queryFn: () => getProduct(slug ?? ""),
    enabled: Boolean(slug),
  });

  const onAddToCart = async () => {
    if (!product) return;

    try {
      await addItem(product, quantity, Boolean(user));
      toast.success("Produit ajouté au panier");
    } catch (e) {
      const message = e instanceof Error ? e.message : "Erreur panier";
      toast.error(message);
    }
  };

  if (isLoading) {
    return <div className="py-16 text-center text-muted-foreground">Chargement...</div>;
  }

  if (error || !product) {
    return (
      <div className="py-16 text-center space-y-4">
        <p className="text-destructive font-medium">Produit introuvable</p>
        <Link to="/" viewTransition>
          <Button variant="outline">Retour boutique</Button>
        </Link>
      </div>
    );
  }

  const inStock = product.stock > 0;

  return (
    <div className="space-y-6">
      <Button variant="ghost" onClick={() => navigate(-1)} className="rounded-xl">
        <ArrowLeft className="h-4 w-4 mr-2" />
        Retour
      </Button>

      <Card className="overflow-hidden">
        <CardContent className="p-0 grid grid-cols-1 lg:grid-cols-2">
          <div className="aspect-square bg-muted flex items-center justify-center">
            {product.imageUrl ? (
              <img src={product.imageUrl} alt={product.name} className="h-full w-full object-cover" />
            ) : (
              <span className="text-6xl opacity-60">🌿</span>
            )}
          </div>

          <div className="p-6 lg:p-8 space-y-5">
            <div className="space-y-2">
              <h1 className="text-3xl font-semibold">{product.name}</h1>
              <p className="text-muted-foreground">{product.description}</p>
            </div>

            <div className="flex items-center gap-3">
              <span className="text-3xl font-bold text-primary">{formatPrice(product.price)}</span>
              {inStock ? (
                <Badge variant="secondary">Stock: {product.stock}</Badge>
              ) : (
                <Badge variant="destructive">Rupture</Badge>
              )}
            </div>

            {inStock && (
              <div className="flex items-center gap-2">
                <Button
                  variant="outline"
                  size="icon"
                  className="rounded-lg"
                  onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                >
                  <Minus className="h-4 w-4" />
                </Button>
                <span className="w-12 text-center font-medium">{quantity}</span>
                <Button
                  variant="outline"
                  size="icon"
                  className="rounded-lg"
                  onClick={() => setQuantity((q) => Math.min(product.stock, q + 1))}
                >
                  <Plus className="h-4 w-4" />
                </Button>
              </div>
            )}

            <Button
              onClick={onAddToCart}
              disabled={!inStock || isCartLoading}
              className="w-full rounded-xl"
              size="lg"
            >
              <ShoppingCart className="h-4 w-4 mr-2" />
              {!inStock ? "Indisponible" : "Ajouter au panier"}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};
