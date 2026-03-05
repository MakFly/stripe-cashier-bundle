<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Integration\Webhook;

use CashierBundle\Contract\WebhookHandlerInterface;
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

    public function testProcessValidWebhookDispatchesEventsAndInvokesMatchingHandler(): void
    {
        $payload = json_encode([
            'id' => 'evt_test_123',
            'object' => 'event',
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'object' => 'subscription',
                    'id' => 'sub_test_123',
                    'status' => 'active',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->generateTestSignature(time(), $payload);
        $eventDispatcher = new EventDispatcher();

        $receivedCalled = 0;
        $handledCalled = 0;
        $matchedHandler = new TrackingHandler(['customer.subscription.created']);
        $otherHandler = new TrackingHandler(['invoice.paid']);

        $eventDispatcher->addListener(WebhookReceivedEvent::class, static function () use (&$receivedCalled): void {
            ++$receivedCalled;
        });

        $eventDispatcher->addListener(WebhookHandledEvent::class, static function () use (&$handledCalled): void {
            ++$handledCalled;
        });

        $handlers = new TestServiceLocator([
            'matched' => $matchedHandler,
            'other' => $otherHandler,
        ]);

        $processor = new WebhookProcessor(
            $handlers,
            $eventDispatcher,
            $this->testSecret,
            300,
        );

        $processor->process($payload, $signature);

        self::assertSame(1, $matchedHandler->handledCount);
        self::assertSame(0, $otherHandler->handledCount);
        self::assertSame(1, $receivedCalled);
        self::assertSame(1, $handledCalled);
    }

    public function testWebhookReceivedEventContainsStripeEvent(): void
    {
        $stripeEvent = $this->createMock(Event::class);
        $event = new WebhookReceivedEvent($stripeEvent);

        self::assertSame($stripeEvent, $event->stripeEvent);
    }

    public function testWebhookHandledEventContainsStripeEvent(): void
    {
        $stripeEvent = $this->createMock(Event::class);
        $event = new WebhookHandledEvent($stripeEvent);

        self::assertSame($stripeEvent, $event->stripeEvent);
    }

    public function testProcessThrowsWhenPayloadIsEmpty(): void
    {
        $processor = $this->createProcessor();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Webhook payload cannot be empty.');

        $processor->process('', 't=123,v1=signature');
    }

    public function testProcessThrowsWhenSignatureIsMissing(): void
    {
        $processor = $this->createProcessor();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stripe signature header is required.');

        $processor->process('{"id":"evt_test"}', null);
    }

    private function generateTestSignature(int $timestamp, string $payload): string
    {
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $this->testSecret);

        return "t={$timestamp},v1={$signature}";
    }

    private function createProcessor(): WebhookProcessor
    {
        return new WebhookProcessor(
            new TestServiceLocator([]),
            $this->createMock(EventDispatcherInterface::class),
            $this->testSecret,
            300,
        );
    }
}

final class TestServiceLocator implements ContainerInterface
{
    /**
     * @param array<string, object> $services
     */
    public function __construct(private array $services)
    {
    }

    public function get(string $id)
    {
        if (!isset($this->services[$id])) {
            throw new \RuntimeException(sprintf('Unknown service id "%s"', $id));
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    /**
     * @return list<string>
     */
    public function getServiceIds(): array
    {
        return array_keys($this->services);
    }

    /**
     * @return array<string, string>
     */
    public function getProvidedServices(): array
    {
        return array_fill_keys(array_keys($this->services), 'object');
    }
}

final class TrackingHandler implements WebhookHandlerInterface
{
    public int $handledCount = 0;

    /**
     * @param list<string> $types
     */
    public function __construct(private array $types)
    {
    }

    public function handles(): array
    {
        return $this->types;
    }

    public function handle(Event $event): void
    {
        ++$this->handledCount;
    }
}
