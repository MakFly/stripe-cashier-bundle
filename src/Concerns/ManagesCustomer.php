<?php

declare(strict_types=1);

namespace CashierBundle\Concerns;

use Stripe\Customer as StripeCustomer;

/**
 * @implements \CashierBundle\Contract\BillableInterface
 */
trait ManagesCustomer
{
    /**
     * Get the Stripe customer ID for the billable entity.
     */
    public function stripeId(): ?string
    {
        return $this->getCashierService('customer')->getStripeId($this);
    }

    /**
     * Determine if the billable entity has a Stripe customer ID.
     */
    public function hasStripeId(): bool
    {
        return $this->getCashierService('customer')->hasStripeId($this);
    }

    /**
     * Create a Stripe customer for the billable entity.
     *
     * @param array<string, mixed> $options
     *
     * @return string The Stripe customer ID
     */
    public function createAsStripeCustomer(array $options = []): string
    {
        return $this->getCashierService('customer')->createCustomer($this, $options);
    }

    /**
     * Update the underlying Stripe customer information.
     *
     * @param array<string, mixed> $options
     */
    public function updateStripeCustomer(array $options = []): void
    {
        $this->getCashierService('customer')->updateCustomer($this, $options);
    }

    /**
     * Get the Stripe customer instance for the billable entity.
     *
     * @param array<string, mixed> $options
     *
     * @return string The Stripe customer ID
     */
    public function createOrGetStripeCustomer(array $options = []): string
    {
        return $this->getCashierService('customer')->createOrGetCustomer($this, $options);
    }

    /**
     * Get the Stripe customer for the billable entity.
     */
    public function asStripeCustomer(): ?StripeCustomer
    {
        return $this->getCashierService('customer')->asStripeCustomer($this);
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
