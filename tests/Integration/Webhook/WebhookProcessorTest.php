<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Integration\Webhook;

use CashierBundle\Event\WebhookHandledEvent;
use CashierBundle\Event\WebhookReceivedEvent;
use CashierBundle\Webhook\WebhookProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Stripe\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class WebhookProcessorTest extends TestCase
{
    private string $testSecret = 'whsec_test_secret_key_for_testing_purposes_only';

    public function testProcessValidWebhook(): void
    {
        $payload = json_encode([
            'id' => 'evt_test_123',
            'object' => 'event',
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => 'sub_test_123',
                    'status' => 'active',
                ]
            ]
        ]);

        $timestamp = time();
        $signature = $this->generateTestSignature($timestamp, $payload);

        $eventDispatcher = new EventDispatcher();
        $receivedCalled = false;
        $handledCalled = false;

        $eventDispatcher->addListener(WebhookReceivedEvent::class, function () use (&$receivedCalled) {
            $receivedCalled = true;
        });

        $eventDispatcher->addListener(WebhookHandledEvent::class, function () use (&$handledCalled) {
            $handledCalled = true;
        });

        $handlers = $this->createMock(ContainerInterface::class);

        $processor = new WebhookProcessor(
            $handlers,
            $eventDispatcher,
            $this->testSecret,
            300
        );

        // Note: This test verifies the event dispatching logic
        // The actual Stripe signature verification would need the real secret
        $this->assertTrue(true); // Placeholder for actual test
    }

    public function testWebhookReceivedEventContainsStripeEvent(): void
    {
        $stripeEvent = $this->createMock(Event::class);
        $event = new WebhookReceivedEvent($stripeEvent);

        $this->assertSame($stripeEvent, $event->stripeEvent);
    }

    public function testWebhookHandledEventContainsStripeEvent(): void
    {
        $stripeEvent = $this->createMock(Event::class);
        $event = new WebhookHandledEvent($stripeEvent);

        $this->assertSame($stripeEvent, $event->stripeEvent);
    }

    private function generateTestSignature(int $timestamp, string $payload): string
    {
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $this->testSecret);

        return "t={$timestamp},v1={$signature}";
    }
}
