<?php

declare(strict_types=1);

namespace CashierBundle\Concerns;

use CashierBundle\Model\CustomerBalanceTransaction;
use CashierBundle\Model\Invoice;
use Doctrine\Common\Collections\Collection;

/**
 * @implements \CashierBundle\Contract\BillableInterface
 */
trait ManagesInvoices
{
    /**
     * Get all of the invoices for the billable entity.
     *
     * @param bool $includePending Include pending invoices
     *
     * @return Collection<int, Invoice>
     */
    public function invoices(bool $includePending = false): Collection
    {
        return $this->getCashierService('invoice')->all($this, $includePending);
    }

    /**
     * Create a new invoice for the billable entity.
     *
     * @param array<string, mixed> $options
     */
    public function invoice(array $options = []): Invoice
    {
        return $this->getCashierService('invoice')->create($this, $options);
    }

    /**
     * Get the upcoming invoice for the billable entity.
     */
    public function upcomingInvoice(): ?Invoice
    {
        return $this->getCashierService('invoice')->upcoming($this);
    }

    /**
     * Add an invoice item to the billable entity.
     *
     * @param array<string, mixed> $options
     *
     * @return static
     */
    public function tab(string $description, int $amount, array $options = []): self
    {
        $this->getCashierService('invoice')->createTabItem($this, $description, $amount, $options);

        return $this;
    }

    /**
     * Invoice the billable entity outside of the regular billing cycle.
     */
    public function invoiceFor(string $description, int $amount): Invoice
    {
        return $this->getCashierService('invoice')->createInvoiceFor($this, $description, $amount);
    }

    /**
     * Get the entity's balance.
     *
     * @return string Integer balance in cents
     */
    public function balance(): string
    {
        return $this->getCashierService('invoice')->getBalance($this);
    }

    /**
     * Credit the entity's balance.
     *
     * @param int $amount Amount in cents
     */
    public function creditBalance(int $amount, ?string $description = null): CustomerBalanceTransaction
    {
        return $this->getCashierService('invoice')->creditBalance($this, $amount, $description);
    }

    /**
     * Debit the entity's balance.
     *
     * @param int $amount Amount in cents
     */
    public function debitBalance(int $amount, ?string $description = null): CustomerBalanceTransaction
    {
        return $this->getCashierService('invoice')->debitBalance($this, $amount, $description);
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
