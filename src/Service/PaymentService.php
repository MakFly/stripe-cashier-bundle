<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Contract\BillableInterface;
use CashierBundle\Exception\IncompletePaymentException;
use CashierBundle\Exception\InvalidPaymentMethodException;
use CashierBundle\Model\Payment;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\StripeClient;

/**
 * Handles charge, pay, and refund operations for billable entities.
 *
 * @implements \CashierBundle\Concerns\PerformsCharges<BillableInterface>
 */
class PaymentService
{
    public function __construct(
        private readonly StripeClient $stripe,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function charge(BillableInterface $billable, int $amount, string $paymentMethod, array $options = []): Payment
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            throw new \RuntimeException('Billable must have a Stripe customer ID.');
        }

        $options = array_merge([
            'amount' => $amount,
            'currency' => Cashier::$currency,
            'payment_method' => $paymentMethod,
            'customer' => $stripeId,
            'confirm' => true,
            'confirmation_method' => PaymentIntent::CONFIRMATION_METHOD_AUTOMATIC,
        ], $options);

        try {
            $paymentIntent = $this->stripe->paymentIntents->create($options);
        } catch (ApiErrorException $e) {
            throw new InvalidPaymentMethodException(
                sprintf('Failed to create payment intent: %s', $e->getMessage()),
                0,
                $e,
            );
        }

        $payment = new Payment($paymentIntent);

        if ($payment->requiresAction() || $payment->requiresPaymentMethod()) {
            throw new IncompletePaymentException($payment);
        }

        return $payment;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function pay(BillableInterface $billable, int $amount, array $options = []): Payment
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            throw new \RuntimeException('Billable must have a Stripe customer ID.');
        }

        // Check if customer has a default payment method
        $customer = $this->stripe->customers->retrieve($stripeId);

        $paymentMethod = $customer->invoice_settings->default_payment_method ?? null;

        if ($paymentMethod === null) {
            throw new InvalidPaymentMethodException('No default payment method found.');
        }

        return $this->charge($billable, $amount, $paymentMethod, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function refund(string $paymentIntent, array $options = []): Refund
    {
        $options = array_merge(['payment_intent' => $paymentIntent], $options);

        try {
            return $this->stripe->refunds->create($options);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to refund payment intent %s: %s', $paymentIntent, $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function refundPartial(string $paymentIntent, int $amount, array $options = []): Refund
    {
        $options = array_merge([
            'payment_intent' => $paymentIntent,
            'amount' => $amount,
        ], $options);

        try {
            return $this->stripe->refunds->create($options);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to refund payment intent %s: %s', $paymentIntent, $e->getMessage()),
                0,
                $e,
            );
        }
    }
}
