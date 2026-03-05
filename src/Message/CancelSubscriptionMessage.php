<?php

declare(strict_types=1);

namespace CashierBundle\Message;

final class CancelSubscriptionMessage
{
    public function __construct(
        public readonly string $subscriptionId,
        public readonly bool $atPeriodEnd = true
    ) {}
}
