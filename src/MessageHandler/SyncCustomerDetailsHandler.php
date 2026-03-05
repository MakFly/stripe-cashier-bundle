<?php

declare(strict_types=1);

namespace CashierBundle\MessageHandler;

use CashierBundle\Message\SyncCustomerDetailsMessage;
use CashierBundle\Service\CustomerService;

class SyncCustomerDetailsHandler
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {
    }

    public function __invoke(SyncCustomerDetailsMessage $message): void
    {
        $this->customerService->syncByStripeId($message->stripeId);
    }
}
