<?php

namespace CashierBundle\Exception;

use Exception;

/** Thrown when a customer balance transaction ID is not valid or not found. */
class InvalidCustomerBalanceTransactionException extends Exception
{
    public static function invalid(string $transactionId): self
    {
        return new self("Invalid customer balance transaction: {$transactionId}");
    }
}
