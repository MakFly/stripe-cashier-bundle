<?php

declare(strict_types=1);

namespace CashierBundle\Message;

/** Message dispatched to asynchronously update the seat quantity of a subscription. */
final class UpdateSubscriptionQuantityMessage
{
    public function __construct(
        public readonly string $subscriptionId,
        public readonly int $quantity,
        public readonly bool $prorate = true,
    ) {
    }
}
