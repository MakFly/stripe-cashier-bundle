import { Link } from "react-router-dom";
import { ShoppingCart, Trash2, Plus, Minus, ShoppingBag } from "lucide-react";
import { toast } from "sonner";
import { Button } from "../ui/button";
import { Badge } from "../ui/badge";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription,
  SheetFooter,
} from "../ui/sheet";
import { useCartStore } from "../../stores/cart";
import { useAuthStore } from "../../stores/auth";
import type { GuestCartItem } from "../../types";

interface CartSheetProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export const CartSheet = ({ open, onOpenChange }: CartSheetProps) => {
  const {
    items,
    guestItems,
    removeItem,
    removeGuestItem,
    updateQuantity,
    updateGuestQuantity,
    getTotal,
    getGuestTotal,
  } = useCartStore();
  const { user } = useAuthStore();

  const displayItems = user ? items : guestItems;
  const total = user ? getTotal() : getGuestTotal();
  const totalItems = user
    ? items.reduce((sum, item) => sum + item.quantity, 0)
    : guestItems.reduce((sum, item) => sum + item.quantity, 0);

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent
        side="right"
        className="flex flex-col p-0 w-full sm:max-w-md"
      >
        {/* Header */}
        <SheetHeader className="px-6 py-5 border-b border-border/50">
          <SheetTitle className="flex items-center gap-2 text-lg">
            <ShoppingCart className="h-5 w-5" />
            Panier
            {totalItems > 0 && (
              <Badge className="ml-1 rounded-full px-2 py-0.5 text-xs bg-primary text-primary-foreground">
                {totalItems}
              </Badge>
            )}
          </SheetTitle>
          <SheetDescription className="sr-only">
            Détails du panier et actions de commande.
          </SheetDescription>
        </SheetHeader>

        {/* Items */}
        <div className="flex-1 overflow-y-auto px-6 py-4">
          {displayItems.length === 0 ? (
            <div className="flex flex-col items-center justify-center h-full gap-4 text-center py-16">
              <div className="w-16 h-16 rounded-2xl bg-secondary flex items-center justify-center">
                <ShoppingBag className="h-8 w-8 text-muted-foreground" />
              </div>
              <div>
                <p className="font-medium text-foreground">Panier vide</p>
                <p className="text-sm text-muted-foreground mt-1">
                  Ajoutez des produits pour commencer
                </p>
              </div>
              <Button
                variant="outline"
                size="sm"
                className="mt-2"
                onClick={() => onOpenChange(false)}
                asChild
              >
                <Link to="/" viewTransition>
                  Voir la boutique
                </Link>
              </Button>
            </div>
          ) : user ? (
            <ul className="space-y-4">
              {items.map((item) => (
                <li
                  key={item.id}
                  className="flex gap-4 py-3 border-b border-border/30 last:border-0"
                >
                  {/* Image placeholder */}
                  <div className="w-16 h-16 rounded-xl bg-secondary flex-shrink-0 overflow-hidden">
                    {item.product.imageUrl ? (
                      <img
                        src={item.product.imageUrl}
                        alt={item.product.name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-muted-foreground text-xs">
                        <ShoppingBag className="h-5 w-5" />
                      </div>
                    )}
                  </div>

                  {/* Info */}
                  <div className="flex-1 min-w-0">
                    <p className="font-medium text-sm truncate">
                      {item.product.name}
                    </p>
                    <p className="text-sm text-muted-foreground mt-0.5">
                      {(item.product.price / 100).toFixed(2)} €
                    </p>

                    {/* Quantity controls */}
                    <div className="flex items-center gap-2 mt-2">
                      <button
                        onClick={() => {
                          if (item.quantity > 1) {
                            updateQuantity(item.id, item.quantity - 1);
                          } else {
                            removeItem(item.id);
                            toast.success("Article retiré");
                          }
                        }}
                        className="w-6 h-6 rounded-md border border-border flex items-center justify-center hover:bg-secondary transition-colors"
                      >
                        <Minus className="h-3 w-3" />
                      </button>
                      <span className="text-sm font-medium w-6 text-center">
                        {item.quantity}
                      </span>
                      <button
                        onClick={() =>
                          updateQuantity(item.id, item.quantity + 1)
                        }
                        className="w-6 h-6 rounded-md border border-border flex items-center justify-center hover:bg-secondary transition-colors"
                      >
                        <Plus className="h-3 w-3" />
                      </button>
                    </div>
                  </div>

                  {/* Subtotal + delete */}
                  <div className="flex flex-col items-end justify-between">
                    <button
                      onClick={() => {
                        removeItem(item.id);
                        toast.success("Article retiré");
                      }}
                      className="text-muted-foreground hover:text-destructive transition-colors"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                    <p className="text-sm font-semibold">
                      {((item.product.price * item.quantity) / 100).toFixed(2)}{" "}
                      €
                    </p>
                  </div>
                </li>
              ))}
            </ul>
          ) : (
            <ul className="space-y-4">
              {guestItems.map((item: GuestCartItem) => (
                <li
                  key={item.productId}
                  className="flex gap-4 py-3 border-b border-border/30 last:border-0"
                >
                  {/* Image */}
                  <div className="w-16 h-16 rounded-xl bg-secondary flex-shrink-0 overflow-hidden">
                    {item.imageUrl ? (
                      <img
                        src={item.imageUrl}
                        alt={item.name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-muted-foreground text-xs">
                        <ShoppingBag className="h-5 w-5" />
                      </div>
                    )}
                  </div>

                  {/* Info */}
                  <div className="flex-1 min-w-0">
                    <p className="font-medium text-sm truncate">{item.name}</p>
                    <p className="text-sm text-muted-foreground mt-0.5">
                      {(item.price / 100).toFixed(2)} €
                    </p>

                    {/* Quantity controls */}
                    <div className="flex items-center gap-2 mt-2">
                      <button
                        onClick={() => {
                          if (item.quantity > 1) {
                            updateGuestQuantity(
                              item.productId,
                              item.quantity - 1,
                            );
                          } else {
                            removeGuestItem(item.productId);
                            toast.success("Article retiré");
                          }
                        }}
                        className="w-6 h-6 rounded-md border border-border flex items-center justify-center hover:bg-secondary transition-colors"
                      >
                        <Minus className="h-3 w-3" />
                      </button>
                      <span className="text-sm font-medium w-6 text-center">
                        {item.quantity}
                      </span>
                      <button
                        onClick={() =>
                          updateGuestQuantity(item.productId, item.quantity + 1)
                        }
                        className="w-6 h-6 rounded-md border border-border flex items-center justify-center hover:bg-secondary transition-colors"
                      >
                        <Plus className="h-3 w-3" />
                      </button>
                    </div>
                  </div>

                  {/* Subtotal + delete */}
                  <div className="flex flex-col items-end justify-between">
                    <button
                      onClick={() => {
                        removeGuestItem(item.productId);
                        toast.success("Article retiré");
                      }}
                      className="text-muted-foreground hover:text-destructive transition-colors"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                    <p className="text-sm font-semibold">
                      {((item.price * item.quantity) / 100).toFixed(2)} €
                    </p>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </div>

        {/* Footer */}
        {displayItems.length > 0 && (
          <SheetFooter className="flex-col gap-3 px-6 py-5 border-t border-border/50 bg-secondary/20">
            <div className="flex items-center justify-between w-full text-base font-semibold">
              <span>Total</span>
              <span>{(total / 100).toFixed(2)} €</span>
            </div>
            <div className="flex flex-col gap-2 w-full">
              {user ? (
                <Button
                  className="w-full"
                  onClick={() => onOpenChange(false)}
                  asChild
                >
                  <Link to="/checkout" viewTransition>
                    Commander
                  </Link>
                </Button>
              ) : (
                <Button
                  className="w-full"
                  onClick={() => onOpenChange(false)}
                  asChild
                >
                  <Link to="/login" viewTransition>
                    Se connecter pour commander
                  </Link>
                </Button>
              )}
              <Button
                variant="outline"
                className="w-full"
                onClick={() => onOpenChange(false)}
                asChild
              >
                <Link to="/cart" viewTransition>
                  Voir le panier complet
                </Link>
              </Button>
            </div>
          </SheetFooter>
        )}
      </SheetContent>
    </Sheet>
  );
};
