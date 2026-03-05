<?php

declare(strict_types=1);

namespace CashierBundle\Concerns;

use CashierBundle\Model\Checkout;
use CashierBundle\Model\Payment;
use Stripe\Refund;

/**
 * @implements \CashierBundle\Contract\BillableInterface
 */
trait PerformsCharges
{
    /**
     * Charge the billable entity with a one-time charge.
     *
     * @param int $amount Amount in cents
     * @param string $paymentMethod The Stripe PaymentMethod ID
     * @param array<string, mixed> $options Additional options for the charge
     */
    public function charge(int $amount, string $paymentMethod, array $options = []): Payment
    {
        return $this->getCashierService('charge')->charge($this, $amount, $paymentMethod, $options);
    }

    /**
     * Charge the billable entity with a one-time charge using their default payment method.
     *
     * @param int $amount Amount in cents
     * @param array<string, mixed> $options Additional options for the charge
     */
    public function pay(int $amount, array $options = []): Payment
    {
        return $this->getCashierService('charge')->pay($this, $amount, $options);
    }

    /**
     * Refund a charge.
     *
     * @param string $paymentIntent The PaymentIntent ID to refund
     * @param array<string, mixed> $options Additional options for the refund
     */
    public function refund(string $paymentIntent, array $options = []): Refund
    {
        return $this->getCashierService('charge')->refund($paymentIntent, $options);
    }

    /**
     * Create a new Checkout session for the given items.
     *
     * @param array<array<string, mixed>> $items Array of items with 'price' and 'quantity' keys
     */
    public function checkout(array $items): Checkout
    {
        return $this->getCashierService('checkout')->create($this, $items);
    }

    /**
     * Create a new Checkout session for a one-time charge.
     *
     * @param int $amount Amount in cents
     * @param string $name The name of the product
     * @param int $quantity The quantity to purchase
     */
    public function checkoutCharge(int $amount, string $name, int $quantity = 1): Checkout
    {
        return $this->getCashierService('checkout')->createCharge($this, $amount, $name, $quantity);
    }

    /**
     * Generate a URL for the billing portal.
     *
     * @param string|null $returnUrl The URL to redirect to after the portal session
     *
     * @return string The billing portal URL
     */
    public function billingPortalUrl(?string $returnUrl = null): string
    {
        return $this->getCashierService('portal')->url($this, $returnUrl);
    }

    /**
     * Get a Cashier service by name.
     *
     * @template T of object
     *
     * @param string $service
     *
     * @return T
     */
    abstract protected function getCashierService(string $service): object;
}
