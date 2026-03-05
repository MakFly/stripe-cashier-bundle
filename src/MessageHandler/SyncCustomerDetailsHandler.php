<?php

declare(strict_types=1);

namespace CashierBundle\MessageHandler;

use CashierBundle\Message\SyncCustomerDetailsMessage;
use CashierBundle\Repository\StripeCustomerRepository;
use CashierBundle\Service\CustomerService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SyncCustomerDetailsHandler
{
    public function __construct(
        private readonly CustomerService $customerService,
        private readonly StripeCustomerRepository $repository,
    ) {
    }

    public function __invoke(SyncCustomerDetailsMessage $message): void
    {
        $customer = $this->repository->findByStripeId($message->stripeId);

        if ($customer) {
            $this->customerService->sync($customer);
        }
    }
}
