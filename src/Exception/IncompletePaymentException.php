<?php

namespace CashierBundle\Exception;

use CashierBundle\Model\Payment;
use Exception;

class IncompletePaymentException extends Exception
{
    public function __construct(
        private readonly Payment $payment,
    ) {
        parent::__construct('The payment attempt requires additional action.');
    }

    public function payment(): Payment
    {
        return $this->payment;
    }
}
