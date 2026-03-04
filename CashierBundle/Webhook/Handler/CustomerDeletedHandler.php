<?php

declare(strict_types=1);

namespace CashierBundle\Webhook\Handler;

use CashierBundle\Repository\StripeCustomerRepository;
use Stripe\Customer as StripeCustomer;
use Stripe\Event;

final readonly class CustomerDeletedHandler extends AbstractWebhookHandler
{
    public function __construct(
        private StripeCustomerRepository $customerRepository
    ) {
    }

    public function handles(): array
    {
        return ['customer.deleted'];
    }

    public function handle(Event $event): void
    {
        $stripeCustomer = $this->getStripeCustomer($event);
        if (!$stripeCustomer instanceof StripeCustomer) {
            return;
        }

        $customer = $this->customerRepository->findOneBy([
            'stripeId' => $stripeCustomer->id,
        ]);

        if ($customer === null) {
            return;
        }

        $this->customerRepository->remove($customer, true);
    }
}
