<?php

declare(strict_types=1);

namespace CashierBundle\Webhook\Handler;

use CashierBundle\Repository\StripeCustomerRepository;
use Stripe\Customer as StripeCustomer;
use Stripe\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles customer.updated by syncing the default payment method onto the local StripeCustomer.
 */
final readonly class CustomerUpdatedHandler extends AbstractWebhookHandler
{
    public function __construct(
        private StripeCustomerRepository $customerRepository,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function handles(): array
    {
        return ['customer.updated'];
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

        $this->updateCustomerFromStripe($customer, $stripeCustomer);
        $this->customerRepository->save($customer, true);
    }

    private function updateCustomerFromStripe(
        \CashierBundle\Entity\StripeCustomer $customer,
        StripeCustomer $stripeCustomer,
    ): void {
        if (isset($stripeCustomer->invoice_settings->default_payment_method)) {
            $paymentMethod = $stripeCustomer->invoice_settings->default_payment_method;

            if (is_string($paymentMethod)) {
                $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethod);
            }

            if ($paymentMethod instanceof \Stripe\PaymentMethod) {
                $customer->setPmType($paymentMethod->type);
                $customer->setPmLastFour($paymentMethod->card->last4 ?? null);
            }
        }
    }
}
