<?php

declare(strict_types=1);

namespace CashierBundle\Contract;

use CashierBundle\Model\Invoice;
use CashierBundle\ValueObject\StoredInvoice;

/**
 * Stores rendered invoice content and returns a value object with its location.
 */
interface InvoiceStorageInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function store(Invoice $invoice, string $contents, array $context = []): StoredInvoice;
}
