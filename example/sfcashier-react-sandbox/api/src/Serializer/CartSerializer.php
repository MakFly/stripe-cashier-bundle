<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\CartItem;
use App\Entity\Product;

class CartSerializer
{
    public function serializeItem(CartItem $item): array
    {
        return [
            '@id'      => '/api/v1/cart/items/' . $item->getId(),
            '@type'    => 'CartItem',
            'id'       => $item->getId(),
            'quantity' => $item->getQuantity(),
            'product'  => $this->serializeProduct($item->getProduct()),
            'subtotal' => $item->getSubtotal(),
        ];
    }

    public function serializeProduct(Product $product): array
    {
        return [
            '@id'         => '/api/v1/products/' . $product->getId(),
            '@type'       => 'Product',
            'id'          => $product->getId(),
            'name'        => $product->getName(),
            'slug'        => $product->getSlug(),
            'price'       => $product->getPrice(),
            'imageUrl'    => $product->getImageUrl(),
            'stock'       => $product->getStock(),
            'description' => $product->getDescription(),
        ];
    }

    /**
     * @param list<array{productId: int, quantity: int}> $items
     * @return list<array{
     *   productId: int,
     *   productIri: string,
     *   quantity: int,
     *   name: string,
     *   slug: string,
     *   price: int,
     *   imageUrl: ?string,
     *   stock: int,
     *   description: ?string
     * }>
     */
    public function serializeGuestItems(array $items): array
    {
        return array_map(
            fn (array $item): array => [
                'productId'   => $item['productId'],
                'productIri'  => '/api/v1/products/' . $item['productId'],
                'quantity'    => $item['quantity'],
                'name'        => $item['name'],
                'slug'        => $item['slug'],
                'price'       => $item['price'],
                'imageUrl'    => $item['imageUrl'],
                'stock'       => $item['stock'],
                'description' => $item['description'],
            ],
            $items,
        );
    }

    /**
     * @param list<array{productId: int, quantity: int}> $items
     * @return list<array{productId: int, quantity: int}>
     */
    public function toCompactItems(array $items): array
    {
        return array_map(
            static fn (array $item): array => [
                'productId' => (int) $item['productId'],
                'quantity' => (int) $item['quantity'],
            ],
            $items,
        );
    }

    public function calculateTotal(array $items): int
    {
        return array_reduce(
            $items,
            static fn (int $sum, array $item): int => $sum + ($item['price'] * $item['quantity']),
            0,
        );
    }
}
