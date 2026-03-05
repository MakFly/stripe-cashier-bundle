<?php

declare(strict_types=1);

namespace CashierBundle\Event;

use Stripe\Event as StripeEvent;
use Symfony\Contracts\EventDispatcher\Event;

final class WebhookReceivedEvent extends Event
{
    public function __construct(
        public readonly StripeEvent $stripeEvent,
    ) {
    }

    public function getStripeEvent(): StripeEvent
    {
        return $this->stripeEvent;
    }
}
