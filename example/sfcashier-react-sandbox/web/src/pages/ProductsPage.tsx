import { useQuery } from "@tanstack/react-query";
import { Link } from "react-router-dom";
import { getProducts } from "../lib/api";
import {
  Card,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Badge } from "../components/ui/badge";
import { formatPrice } from "../lib/utils";

export const ProductsPage = () => {
  const {
    data: productsData,
    isLoading,
    error,
  } = useQuery({
    queryKey: ["products"],
    queryFn: getProducts,
  });

  if (isLoading) {
    return (
      <div className="flex flex-col justify-center items-center min-h-[60vh] gap-4">
        <div className="w-16 h-16 rounded-2xl bg-primary/10" />
        <p className="text-muted-foreground text-sm">Chargement...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex flex-col justify-center items-center min-h-[60vh] gap-4">
        <p className="text-destructive font-medium">Une erreur est survenue</p>
        <p className="text-muted-foreground text-sm">
          Veuillez rafraîchir la page
        </p>
      </div>
    );
  }

  const products = productsData?.member || [];

  return (
    <div className="space-y-12">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-medium mb-2">Nos Produits</h1>
        <p className="text-muted-foreground">
          {products.length === 0
            ? "Aucun produit disponible"
            : `${products.length} produit${products.length > 1 ? "s" : ""} disponible${products.length > 1 ? "s" : ""}`}
        </p>
      </div>

      {products.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-24 px-4 rounded-3xl border-2 border-dashed border-border/50">
          <p className="text-lg font-medium mb-2">
            Aucun produit pour le moment
          </p>
          <p className="text-muted-foreground text-sm text-center max-w-sm">
            Notre collection s'enrichit bientôt de nouvelles créations.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          {products.map((product) => (
            <Link
              key={product.id}
              to={`/product/${product.slug}`}
              className="group block"
            >
              <Card className="h-full overflow-hidden">
                {/* Image Container */}
                <div className="relative aspect-square overflow-hidden bg-muted">
                  {product.imageUrl ? (
                    <img
                      src={product.imageUrl}
                      alt={product.name}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center">
                      <span className="text-4xl opacity-60">🌿</span>
                    </div>
                  )}

                  {/* Stock Badge */}
                  {product.stock <= 0 && (
                    <div className="absolute top-4 left-4">
                      <Badge variant="destructive">Épuisé</Badge>
                    </div>
                  )}
                  {product.stock > 0 && product.stock <= 5 && (
                    <div className="absolute top-4 left-4">
                      <Badge variant="secondary">Plus que {product.stock}</Badge>
                    </div>
                  )}
                </div>

                {/* Content */}
                <CardHeader className="pb-3">
                  <CardTitle className="text-lg">{product.name}</CardTitle>
                  <CardDescription className="line-clamp-2 text-sm">
                    {product.description}
                  </CardDescription>
                </CardHeader>

                <CardFooter className="pt-0 flex items-center justify-between">
                  <span className="text-xl font-semibold text-primary">
                    {formatPrice(product.price)}
                  </span>
                  <Button variant="secondary" size="sm">
                    Voir
                  </Button>
                </CardFooter>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
};
