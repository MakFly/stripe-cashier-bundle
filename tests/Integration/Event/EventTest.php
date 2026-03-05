<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Integration\Event;

use CashierBundle\Entity\Subscription;
use CashierBundle\Event\SubscriptionCreatedEvent;
use CashierBundle\Event\SubscriptionUpdatedEvent;
use CashierBundle\Event\SubscriptionDeletedEvent;
use CashierBundle\Event\PaymentSucceededEvent;
use CashierBundle\Event\PaymentFailedEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

final class EventTest extends TestCase
{
    public function testSubscriptionCreatedEventExtendsSymfonyEvent(): void
    {
        $subscription = $this->createMock(Subscription::class);
        $event = new SubscriptionCreatedEvent($subscription);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame($subscription, $event->subscription);
    }

    public function testSubscriptionUpdatedEventExtendsSymfonyEvent(): void
    {
        $subscription = $this->createMock(Subscription::class);
        $event = new SubscriptionUpdatedEvent($subscription);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame($subscription, $event->subscription);
    }

    public function testSubscriptionDeletedEventExtendsSymfonyEvent(): void
    {
        $subscription = $this->createMock(Subscription::class);
        $event = new SubscriptionDeletedEvent($subscription);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame($subscription, $event->subscription);
    }

    public function testPaymentSucceededEventContainsPayment(): void
    {
        $event = new PaymentSucceededEvent(
            customerId: 'cus_test_123',
            paymentIntentId: 'pi_test',
            amount: 1000,
            currency: 'usd'
        );

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('cus_test_123', $event->customerId);
        $this->assertSame('pi_test', $event->paymentIntentId);
        $this->assertSame(1000, $event->amount);
        $this->assertSame('usd', $event->currency);
    }

    public function testPaymentFailedEventContainsPayment(): void
    {
        $event = new PaymentFailedEvent(
            customerId: 'cus_test_456',
            paymentIntentId: 'pi_test_2',
            amount: 2000,
            currency: 'eur'
        );

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('cus_test_456', $event->customerId);
        $this->assertSame('pi_test_2', $event->paymentIntentId);
        $this->assertSame(2000, $event->amount);
        $this->assertSame('eur', $event->currency);
    }
}
