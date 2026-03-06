<?php

declare(strict_types=1);

namespace CashierBundle\MessageHandler;

use CashierBundle\Message\UpdateSubscriptionQuantityMessage;
use CashierBundle\Service\SubscriptionService;

/** Handles UpdateSubscriptionQuantityMessage by updating the subscription seat count via SubscriptionService. */
class UpdateSubscriptionQuantityHandler
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    public function __invoke(UpdateSubscriptionQuantityMessage $message): void
    {
        $this->subscriptionService->updateQuantity(
            $message->subscriptionId,
            $message->quantity,
            $message->prorate,
        );
    }
}
