<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service;

use CashierBundle\Service\CheckoutService;
use CashierBundle\Tests\Support\FakeBillable;
use CashierBundle\Tests\Support\TestStripeClient;
use PHPUnit\Framework\TestCase;
use Stripe\Exception\InvalidRequestException;

/** Test suite for CheckoutService. */
final class CheckoutServiceTest extends TestCase
{
    public function testCreateThrowsWhenBillableHasNoStripeId(): void
    {
        $service = new CheckoutService(new TestStripeClient());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Billable must have a Stripe customer ID.');

        $service->create(new FakeBillable(null), [['price' => 'price_1', 'quantity' => 1]]);
    }

    public function testBillingPortalReturnsUrl(): void
    {
        $stripe = (new TestStripeClient())->withService('billingPortal', (object) [
            'sessions' => new class () {
                /**
                 * @param array<string, mixed> $payload
                 */
                public function create(array $payload): object
                {
                    return (object) ['url' => 'https://billing.example.test/session'];
                }
            },
        ]);

        $service = new CheckoutService($stripe);

        self::assertSame(
            'https://billing.example.test/session',
            $service->billingPortal(new FakeBillable('cus_123')),
        );
    }

    public function testFindSessionReturnsNullOnStripeError(): void
    {
        $stripe = (new TestStripeClient())->withService('checkout', (object) [
            'sessions' => new class () {
                public function retrieve(string $sessionId): object
                {
                    throw new InvalidRequestException('missing');
                }
            },
        ]);

        $service = new CheckoutService($stripe);

        self::assertNull($service->findSession('cs_missing'));
    }

    public function testCreateSubscriptionReturnsCheckout(): void
    {
        $sessions = new CheckoutSessionsSpy();
        $stripe = (new TestStripeClient())->withService('checkout', (object) [
            'sessions' => $sessions,
        ]);

        $service = new CheckoutService($stripe);

        $checkout = $service->createSubscription(
            new FakeBillable('cus_123'),
            [['price' => 'price_monthly', 'quantity' => 2]],
            [
                'metadata' => [
                    'app_resource_type' => 'subscription_plan',
                    'app_resource_id' => 'starter',
                    'plan_code' => 'starter',
                ],
                'subscription_data' => [
                    'trial_period_days' => 14,
                ],
            ],
        );

        self::assertSame('cs_test_raw', $checkout->id());
        self::assertSame('sub_123', $checkout->subscriptionId());
        self::assertSame('subscription_plan', $sessions->capturedPayload['metadata']['app_resource_type']);
        self::assertSame('starter', $sessions->capturedPayload['metadata']['app_resource_id']);
        self::assertSame('starter', $sessions->capturedPayload['subscription_data']['metadata']['plan_code']);
        self::assertSame(14, $sessions->capturedPayload['subscription_data']['trial_period_days']);
    }

    public function testCreateAcceptsRawStripeLineItems(): void
    {
        $sessions = new CheckoutSessionsSpy();
        $stripe = (new TestStripeClient())->withService('checkout', (object) [
            'sessions' => $sessions,
        ]);

        $service = new CheckoutService($stripe);

        $checkout = $service->create(new FakeBillable('cus_123'), [[
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => 1999,
                'product_data' => [
                    'name' => 'Blender Pro',
                ],
            ],
            'quantity' => 2,
        ]]);

        self::assertSame('cs_test_raw', $checkout->id());
        self::assertSame('pi_test_raw', $checkout->paymentIntentId());
        self::assertSame('Blender Pro', $sessions->capturedPayload['line_items'][0]['price_data']['product_data']['name']);
        self::assertSame(2, $sessions->capturedPayload['line_items'][0]['quantity']);
        self::assertSame('cus_123', $sessions->capturedPayload['customer']);
        self::assertTrue($sessions->capturedPayload['invoice_creation']['enabled']);
    }

    public function testCreateCopiesMetadataToInvoiceCreationPayload(): void
    {
        $sessions = new CheckoutSessionsSpy();
        $stripe = (new TestStripeClient())->withService('checkout', (object) [
            'sessions' => $sessions,
        ]);

        $service = new CheckoutService($stripe);

        $service->create(new FakeBillable('cus_123'), [[
            'price' => 'price_test_123',
            'quantity' => 1,
        ]], [
            'metadata' => [
                'app_order_id' => '42',
                'app_user_id' => '7',
            ],
        ]);

        self::assertSame(
            [
                'app_order_id' => '42',
                'app_user_id' => '7',
            ],
            $sessions->capturedPayload['invoice_creation']['invoice_data']['metadata'] ?? null,
        );
    }
}

/** Spy for Stripe checkout sessions capturing the submitted payload. */
final class CheckoutSessionsSpy
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $capturedPayload = null;

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): object
    {
        $this->capturedPayload = $payload;

        return (object) [
            'id' => 'cs_test_raw',
            'url' => 'https://checkout.example.test/raw',
            'payment_intent' => 'pi_test_raw',
            'setup_intent' => null,
            'customer' => $payload['customer'],
            'subscription' => $payload['mode'] === 'subscription' ? 'sub_123' : null,
            'status' => 'open',
        ];
    }
}
