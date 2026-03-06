<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Model;

use CashierBundle\Model\Checkout;
use PHPUnit\Framework\TestCase;

/** Test suite for Checkout. */
final class CheckoutTest extends TestCase
{
    private function createSession(array $data = []): object
    {
        return new class ($data) {
            public string $id;
            public ?string $url;
            public ?string $payment_intent;
            public ?string $setup_intent;
            public ?string $customer;
            public ?string $subscription;
            public string $status;

            public function __construct(array $data)
            {
                $this->id = $data['id'] ?? 'cs_test_123';
                $this->url = $data['url'] ?? 'https://checkout.stripe.com/test';
                $this->payment_intent = $data['payment_intent'] ?? null;
                $this->setup_intent = $data['setup_intent'] ?? null;
                $this->customer = $data['customer'] ?? 'cus_test_123';
                $this->subscription = $data['subscription'] ?? null;
                $this->status = $data['status'] ?? 'open';
            }
        };
    }

    public function testIdReturnsCorrectValue(): void
    {
        $session = $this->createSession(['id' => 'cs_abc']);
        $checkout = new Checkout($session);

        $this->assertEquals('cs_abc', $checkout->id());
    }

    public function testUrlReturnsCorrectValue(): void
    {
        $session = $this->createSession(['url' => 'https://checkout.example.com']);
        $checkout = new Checkout($session);

        $this->assertEquals('https://checkout.example.com', $checkout->url());
    }

    public function testPaymentIntentIdReturnsCorrectValue(): void
    {
        $session = $this->createSession(['payment_intent' => 'pi_test_456']);
        $checkout = new Checkout($session);

        $this->assertEquals('pi_test_456', $checkout->paymentIntentId());
    }

    public function testSubscriptionIdReturnsCorrectValue(): void
    {
        $session = $this->createSession(['subscription' => 'sub_test_789']);
        $checkout = new Checkout($session);

        $this->assertEquals('sub_test_789', $checkout->subscriptionId());
    }

    public function testIsCompleteReturnsTrue(): void
    {
        $session = $this->createSession(['status' => 'complete']);
        $checkout = new Checkout($session);

        $this->assertTrue($checkout->isComplete());
    }

    public function testIsExpiredReturnsTrue(): void
    {
        $session = $this->createSession(['status' => 'expired']);
        $checkout = new Checkout($session);

        $this->assertTrue($checkout->isExpired());
    }

    public function testIsOpenReturnsTrue(): void
    {
        $session = $this->createSession(['status' => 'open']);
        $checkout = new Checkout($session);

        $this->assertTrue($checkout->isOpen());
    }
}
