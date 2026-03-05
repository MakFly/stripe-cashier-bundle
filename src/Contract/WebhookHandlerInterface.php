<?php

declare(strict_types=1);

namespace CashierBundle\Contract;

use Stripe\Event;

interface WebhookHandlerInterface
{
    /**
     * @return array<string> List of event types this handler handles
     */
    public function handles(): array;

    public function handle(Event $stripeEvent): void;
}
