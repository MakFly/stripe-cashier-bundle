<?php

declare(strict_types=1);

namespace CashierBundle\Concerns;

use CashierBundle\Contract\BillableInterface;

/**
 * @implements BillableInterface
 */
trait HandlesTaxes
{
    /**
     * Get the tax rates for the billable entity.
     *
     * @return array<string>
     */
    public function taxRates(): array
    {
        return $this->getCashierService('tax')->getTaxRatesForEntity($this);
    }

    /**
     * Determine if the billable entity is tax exempt.
     */
    public function isTaxExempt(): bool
    {
        return $this->getCashierService('tax')->isTaxExempt($this);
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
