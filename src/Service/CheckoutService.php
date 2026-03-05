<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Contract\BillableInterface;
use CashierBundle\Model\Checkout;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * @phpstan-type CheckoutItem array{price: string, quantity: int|null}
 */
class CheckoutService
{
    public function __construct(
        private readonly StripeClient $stripe,
    ) {
    }

    /**
     * @param array<CheckoutItem> $items
     * @param array<string, mixed> $options
     */
    public function create(BillableInterface $billable, array $items, array $options = []): Checkout
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            throw new \RuntimeException('Billable must have a Stripe customer ID.');
        }

        $payload = array_merge([
            'customer' => $stripeId,
            'mode' => 'payment',
            'line_items' => array_map(fn ($item) => [
                'price' => $item['price'],
                'quantity' => $item['quantity'] ?? 1,
            ], $items),
        ], $options);

        try {
            $session = $this->stripe->checkout->sessions->create($payload);

            return new Checkout($session);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to create checkout session: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function charge(BillableInterface $billable, int $amount, string $name, int $quantity = 1, array $options = []): Checkout
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            throw new \RuntimeException('Billable must have a Stripe customer ID.');
        }

        $payload = array_merge([
            'customer' => $stripeId,
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => Cashier::$currency,
                    'product_data' => [
                        'name' => $name,
                    ],
                    'unit_amount' => $amount,
                ],
                'quantity' => $quantity,
            ]],
        ], $options);

        try {
            $session = $this->stripe->checkout->sessions->create($payload);

            return new Checkout($session);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to create checkout charge session: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    public function billingPortal(BillableInterface $billable, ?string $returnUrl = null): string
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            throw new \RuntimeException('Billable must have a Stripe customer ID.');
        }

        $payload = ['customer' => $stripeId];
        if ($returnUrl !== null) {
            $payload['return_url'] = $returnUrl;
        }

        try {
            $session = $this->stripe->billingPortal->sessions->create($payload);

            return $session->url;
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to create billing portal session: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    public function findSession(string $sessionId): ?Checkout
    {
        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);

            return new Checkout($session);
        } catch (ApiErrorException $e) {
            return null;
        }
    }

    /**
     * @param array<CheckoutItem> $items
     * @param array<string, mixed> $options
     */
    public function createSubscription(BillableInterface $billable, array $items, array $options = []): Checkout
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            throw new \RuntimeException('Billable must have a Stripe customer ID.');
        }

        $payload = array_merge([
            'customer' => $stripeId,
            'mode' => 'subscription',
            'line_items' => array_map(fn ($item) => [
                'price' => $item['price'],
                'quantity' => $item['quantity'] ?? 1,
            ], $items),
        ], $options);

        try {
            $session = $this->stripe->checkout->sessions->create($payload);

            return new Checkout($session);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to create subscription checkout session: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }
}
