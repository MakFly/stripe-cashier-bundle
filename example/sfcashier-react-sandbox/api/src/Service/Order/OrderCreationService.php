<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderStatus;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class OrderCreationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function createFromCart(User $user, Cart $cart): Order
    {
        $order = new Order();
        $order->setUser($user);
        $order->setTotal($cart->getTotal());
        $order->setStatus(OrderStatus::PENDING);

        foreach ($cart->getItems() as $cartItem) {
            $product = $cartItem->getProduct();
            if ($product === null) {
                continue;
            }

            $orderItem = new OrderItem();
            $orderItem
                ->setProductId((int) $product->getId())
                ->setProductName($product->getName())
                ->setProductSlug($product->getSlug())
                ->setProductDescription($product->getDescription())
                ->setProductImageUrl($product->getImageUrl())
                ->setUnitPrice($product->getPrice())
                ->setQuantity($cartItem->getQuantity())
                ->setSubtotal($product->getPrice() * $cartItem->getQuantity());

            $order->addItem($orderItem);
        }

        $this->entityManager->persist($order);

        return $order;
    }

    public function clearCart(Cart $cart): void
    {
        foreach ($cart->getItems() as $item) {
            $this->entityManager->remove($item);
        }
    }

    public function discard(Order $order): void
    {
        $this->entityManager->remove($order);
        $this->entityManager->flush();
    }
}
