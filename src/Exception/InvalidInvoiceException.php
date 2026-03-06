<?php

namespace CashierBundle\Exception;

use Exception;

/** Thrown when an invoice ID is invalid or does not belong to the given customer. */
class InvalidInvoiceException extends Exception
{
    public static function invalid(string $invoiceId): self
    {
        return new self("Invalid invoice: {$invoiceId}");
    }

    public static function notBelongToCustomer(string $invoiceId, string $customerId): self
    {
        return new self("Invoice {$invoiceId} does not belong to customer {$customerId}");
    }
}
