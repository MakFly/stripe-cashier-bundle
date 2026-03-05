<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Contract\BillableInterface;
use CashierBundle\Model\Checkout;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * @phpstan-type CheckoutItem array{price?: string, quantity?: int|null}
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

        $metadata = [];
        if (isset($options['metadata']) && is_array($options['metadata'])) {
            /** @var array<string, mixed> $metadata */
            $metadata = $options['metadata'];
        }

        $payload = array_merge([
            'customer' => $stripeId,
            'mode' => 'payment',
            'invoice_creation' => [
                'enabled' => true,
                'invoice_data' => [
                    'metadata' => $metadata,
                ],
            ],
            'line_items' => array_map(
                static fn (array $item): array => self::normalizeLineItem($item),
                $items,
            ),
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
            'invoice_creation' => [
                'enabled' => true,
            ],
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

        $metadata = [];
        if (isset($options['metadata']) && is_array($options['metadata'])) {
            /** @var array<string, mixed> $metadata */
            $metadata = $options['metadata'];
        }

        $subscriptionData = [];
        if (isset($options['subscription_data']) && is_array($options['subscription_data'])) {
            /** @var array<string, mixed> $subscriptionData */
            $subscriptionData = $options['subscription_data'];
        }

        if ($metadata !== [] && !isset($subscriptionData['metadata'])) {
            $subscriptionData['metadata'] = $metadata;
        }

        $payload = array_merge([
            'customer' => $stripeId,
            'mode' => 'subscription',
            'line_items' => array_map(fn ($item) => [
                'price' => $item['price'],
                'quantity' => $item['quantity'] ?? 1,
            ], $items),
            'metadata' => $metadata,
            'subscription_data' => $subscriptionData,
        ], $options);

        if ($subscriptionData !== []) {
            $payload['subscription_data'] = array_merge(
                $subscriptionData,
                is_array($options['subscription_data'] ?? null) ? $options['subscription_data'] : [],
            );

            if ($metadata !== [] && !isset($payload['subscription_data']['metadata'])) {
                $payload['subscription_data']['metadata'] = $metadata;
            }
        }

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

    /**
     * @param array<string, mixed> $options
     */
    public function createCharge(BillableInterface $billable, int $amount, string $name, int $quantity = 1, array $options = []): Checkout
    {
        return $this->charge($billable, $amount, $name, $quantity, $options);
    }

    public function url(BillableInterface $billable, ?string $returnUrl = null): string
    {
        return $this->billingPortal($billable, $returnUrl);
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>
     */
    private static function normalizeLineItem(array $item): array
    {
        if (isset($item['price'])) {
            return [
                'price' => $item['price'],
                'quantity' => $item['quantity'] ?? 1,
            ];
        }

        if (!isset($item['quantity'])) {
            $item['quantity'] = 1;
        }

        return $item;
    }
}
