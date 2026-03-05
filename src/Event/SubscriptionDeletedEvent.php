<?php

declare(strict_types=1);

namespace CashierBundle\Event;

use CashierBundle\Entity\Subscription;
use Symfony\Contracts\EventDispatcher\Event;

final class SubscriptionDeletedEvent extends Event
{
    public function __construct(
        public readonly Subscription $subscription,
    ) {
    }

    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }
}
