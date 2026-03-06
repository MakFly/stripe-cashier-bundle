<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CartItem;
use App\Entity\Cart;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartItem>
 */
class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    public function findByCartAndProduct(Cart $cart, Product $product): ?CartItem
    {
        return $this->findOneBy(['cart' => $cart, 'product' => $product]);
    }

    public function addItemOrUpdateQuantity(Cart $cart, Product $product, int $quantity): CartItem
    {
        $item = $this->findByCartAndProduct($cart, $product);

        if ($item === null) {
            $item = new CartItem();
            $cart->addItem($item);
            $item->setProduct($product);
            $item->setQuantity($quantity);
            $this->getEntityManager()->persist($item);
        } else {
            $item->setQuantity($item->getQuantity() + $quantity);
        }

        $this->getEntityManager()->flush();

        return $item;
    }

    public function save(CartItem $item): void
    {
        $this->getEntityManager()->flush();
    }

    public function removeItem(CartItem $item): void
    {
        $this->getEntityManager()->remove($item);
        $this->getEntityManager()->flush();
    }
}
