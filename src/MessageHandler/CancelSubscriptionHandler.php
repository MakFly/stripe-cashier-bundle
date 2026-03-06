<?php

declare(strict_types=1);

namespace CashierBundle\MessageHandler;

use CashierBundle\Message\CancelSubscriptionMessage;
use CashierBundle\Service\SubscriptionService;

/** Handles CancelSubscriptionMessage by delegating subscription cancellation to SubscriptionService. */
class CancelSubscriptionHandler
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    public function __invoke(CancelSubscriptionMessage $message): void
    {
        $this->subscriptionService->cancel(
            $message->subscriptionId,
            $message->atPeriodEnd,
        );
    }
}
