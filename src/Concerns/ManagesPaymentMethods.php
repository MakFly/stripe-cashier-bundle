<?php

declare(strict_types=1);

namespace CashierBundle\Concerns;

use CashierBundle\Model\PaymentMethod;
use Doctrine\Common\Collections\Collection;

/**
 * @implements \CashierBundle\Contract\BillableInterface
 */
trait ManagesPaymentMethods
{
    /**
     * Determine if the billable entity has a default payment method.
     */
    public function hasDefaultPaymentMethod(): bool
    {
        return $this->getCashierService('payment_method')->hasDefault($this);
    }

    /**
     * Get the default payment method for the billable entity.
     */
    public function defaultPaymentMethod(): ?PaymentMethod
    {
        return $this->getCashierService('payment_method')->getDefault($this);
    }

    /**
     * Add a payment method to the billable entity.
     *
     * @param string $paymentMethod The Stripe PaymentMethod ID
     */
    public function addPaymentMethod(string $paymentMethod): PaymentMethod
    {
        return $this->getCashierService('payment_method')->add($this, $paymentMethod);
    }

    /**
     * Update the default payment method for the billable entity.
     *
     * @param string $paymentMethod The Stripe PaymentMethod ID
     */
    public function updateDefaultPaymentMethod(string $paymentMethod): PaymentMethod
    {
        return $this->getCashierService('payment_method')->updateDefault($this, $paymentMethod);
    }

    /**
     * Get a collection of the entity's payment methods.
     *
     * @param string|null $type The type of payment methods to retrieve (e.g., 'card', 'us_bank_account')
     *
     * @return Collection<int, PaymentMethod>
     */
    public function paymentMethods(?string $type = null): Collection
    {
        return $this->getCashierService('payment_method')->all($this, $type);
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
