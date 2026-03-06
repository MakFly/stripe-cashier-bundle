<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Exception\IncompletePaymentException;
use CashierBundle\Exception\InvalidPaymentMethodException;
use CashierBundle\Model\Payment;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\StripeClient;

/** Manages Stripe PaymentIntent lifecycle operations. */
class PaymentIntentService
{
    public function __construct(
        private readonly StripeClient $stripe,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function create(int $amount, string $currency, array $options = []): Payment
    {
        $payload = array_merge([
            'amount' => $amount,
            'currency' => $currency,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ], $options);

        try {
            $paymentIntent = $this->stripe->paymentIntents->create($payload);

            return new Payment($paymentIntent);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to create payment intent: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    public function find(string $id): ?Payment
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($id);

            return new Payment($paymentIntent);
        } catch (ApiErrorException $e) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function capture(string $id, array $options = []): Payment
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->capture($id, $options);

            return new Payment($paymentIntent);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to capture payment intent %s: %s', $id, $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function cancel(string $id, array $options = []): Payment
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->cancel($id, $options);

            return new Payment($paymentIntent);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to cancel payment intent %s: %s', $id, $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function confirm(string $id, array $options = []): Payment
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->confirm($id, $options);
            $payment = new Payment($paymentIntent);

            if ($payment->requiresAction()) {
                throw new IncompletePaymentException($payment);
            }

            return $payment;
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to confirm payment intent %s: %s', $id, $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function update(string $id, array $options = []): Payment
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->update($id, $options);

            return new Payment($paymentIntent);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to update payment intent %s: %s', $id, $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function authorize(int $amount, string $currency, string $paymentMethod, ?string $customerId = null, array $options = []): array
    {
        $payload = array_merge([
            'amount' => $amount,
            'currency' => $currency,
            'payment_method' => $paymentMethod,
            'capture_method' => StripePaymentIntent::CAPTURE_METHOD_MANUAL,
            'confirmation_method' => StripePaymentIntent::CONFIRMATION_METHOD_AUTOMATIC,
            'confirm' => true,
        ], $options);

        if ($customerId !== null) {
            $payload['customer'] = $customerId;
        }

        try {
            $paymentIntent = $this->stripe->paymentIntents->create($payload);

            $payment = new Payment($paymentIntent);

            if ($payment->requiresAction()) {
                throw new IncompletePaymentException($payment);
            }

            return [
                'id' => $payment->id(),
                'client_secret' => $payment->clientSecret(),
                'status' => $payment->status(),
                'amount' => $payment->rawAmount(),
                'currency' => $payment->currency(),
            ];
        } catch (ApiErrorException $e) {
            throw new InvalidPaymentMethodException(
                sprintf('Failed to authorize payment: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }
}
