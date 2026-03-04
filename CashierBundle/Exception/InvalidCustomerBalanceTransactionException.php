<?php

namespace CashierBundle\Exception;

use Exception;

class InvalidCustomerBalanceTransactionException extends Exception
{
    public static function invalid(string $transactionId): self
    {
        return new self("Invalid customer balance transaction: {$transactionId}");
    }
}
