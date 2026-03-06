<?php

namespace CashierBundle\Exception;

use CashierBundle\Model\Payment;
use Exception;

/** Thrown when a payment attempt requires additional customer action (e.g. 3DS). */
class IncompletePaymentException extends Exception
{
    public function __construct(
        private readonly Payment $payment,
    ) {
        parent::__construct('The payment attempt requires additional action.');
    }

    /** Returns the Payment object that requires further action. */
    public function payment(): Payment
    {
        return $this->payment;
    }
}
