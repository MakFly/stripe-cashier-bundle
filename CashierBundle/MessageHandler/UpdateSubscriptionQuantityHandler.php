<?php

declare(strict_types=1);

namespace CashierBundle\MessageHandler;

use CashierBundle\Message\UpdateSubscriptionQuantityMessage;
use CashierBundle\Service\SubscriptionService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateSubscriptionQuantityHandler
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    public function __invoke(UpdateSubscriptionQuantityMessage $message): void
    {
        $this->subscriptionService->updateQuantity(
            $message->subscriptionId,
            $message->quantity,
            $message->prorate
        );
    }
}
