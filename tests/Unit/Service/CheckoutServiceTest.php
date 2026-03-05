<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service;

use CashierBundle\Service\CheckoutService;
use CashierBundle\Tests\Support\FakeBillable;
use PHPUnit\Framework\TestCase;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;

final class CheckoutServiceTest extends TestCase
{
    public function testCreateThrowsWhenBillableHasNoStripeId(): void
    {
        $service = new CheckoutService(new StripeClient('sk_test'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Billable must have a Stripe customer ID.');

        $service->create(new FakeBillable(null), [['price' => 'price_1', 'quantity' => 1]]);
    }

    public function testBillingPortalReturnsUrl(): void
    {
        $stripe = new StripeClient('sk_test');
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->billingPortal = (object) [
            'sessions' => new class () {
                /**
                 * @param array<string, mixed> $payload
                 */
                public function create(array $payload): object
                {
                    return (object) ['url' => 'https://billing.example.test/session'];
                }
            },
        ];

        $service = new CheckoutService($stripe);

        self::assertSame(
            'https://billing.example.test/session',
            $service->billingPortal(new FakeBillable('cus_123')),
        );
    }

    public function testFindSessionReturnsNullOnStripeError(): void
    {
        $stripe = new StripeClient('sk_test');
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->checkout = (object) [
            'sessions' => new class () {
                public function retrieve(string $sessionId): object
                {
                    throw new InvalidRequestException('missing');
                }
            },
        ];

        $service = new CheckoutService($stripe);

        self::assertNull($service->findSession('cs_missing'));
    }

    public function testCreateSubscriptionReturnsCheckout(): void
    {
        $stripe = new StripeClient('sk_test');
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->checkout = (object) [
            'sessions' => new class () {
                /**
                 * @param array<string, mixed> $payload
                 */
                public function create(array $payload): object
                {
                    return (object) [
                        'id' => 'cs_test_123',
                        'url' => 'https://checkout.example.test',
                        'payment_intent' => null,
                        'setup_intent' => null,
                        'customer' => $payload['customer'],
                        'subscription' => 'sub_123',
                        'status' => 'open',
                    ];
                }
            },
        ];

        $service = new CheckoutService($stripe);

        $checkout = $service->createSubscription(
            new FakeBillable('cus_123'),
            [['price' => 'price_monthly', 'quantity' => 2]],
        );

        self::assertSame('cs_test_123', $checkout->id());
        self::assertSame('sub_123', $checkout->subscriptionId());
    }
}
