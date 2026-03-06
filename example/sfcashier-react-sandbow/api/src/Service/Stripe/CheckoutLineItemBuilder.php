<?php

declare(strict_types=1);

namespace App\Service\Stripe;

use App\Entity\Order;

class CheckoutLineItemBuilder
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function build(Order $order): array
    {
        $lineItems = [];

        foreach ($order->getItems() as $orderItem) {
            $lineItem = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $orderItem->getUnitPrice(),
                    'product_data' => [
                        'name' => $orderItem->getProductName(),
                    ],
                ],
                'quantity' => $orderItem->getQuantity(),
            ];

            if ($orderItem->getProductDescription() !== null) {
                $lineItem['price_data']['product_data']['description'] = $orderItem->getProductDescription();
            }

            if ($orderItem->getProductImageUrl() !== null) {
                $lineItem['price_data']['product_data']['images'] = [$orderItem->getProductImageUrl()];
            }

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }
}
