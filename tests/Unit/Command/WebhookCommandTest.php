<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Command;

use CashierBundle\Command\WebhookCommand;
use CashierBundle\Tests\Support\TestStripeClient;
use PHPUnit\Framework\TestCase;
use Stripe\StripeClient;
use Symfony\Component\Console\Tester\CommandTester;

/** Test suite for WebhookCommand. */
final class WebhookCommandTest extends TestCase
{
    public function testSecretIsMaskedByDefault(): void
    {
        $command = new WebhookCommand($this->createStripeClient(), []);
        $tester = new CommandTester($command);

        $tester->execute([
            '--url' => 'https://example.test/webhook',
        ]);

        $display = $tester->getDisplay();

        self::assertStringContainsString('whse********7890', $display);
        self::assertStringNotContainsString('whsec_1234567890', $display);
        self::assertStringContainsString('Secret is masked by default', $display);
    }

    public function testShowSecretOptionPrintsClearSecret(): void
    {
        $command = new WebhookCommand($this->createStripeClient(), []);
        $tester = new CommandTester($command);

        $tester->execute([
            '--url' => 'https://example.test/webhook',
            '--show-secret' => true,
        ]);

        $display = $tester->getDisplay();

        self::assertStringContainsString('STRIPE_WEBHOOK_SECRET=whsec_1234567890', $display);
    }

    public function testConfiguredEventsAreMergedWithDefaults(): void
    {
        $spy = new class () {
            /** @var array<string, mixed>|null */
            public static ?array $capturedParams = null;

            /**
             * @param array<string, mixed> $params
             */
            public function create(array $params): object
            {
                self::$capturedParams = $params;

                return (object) [
                    'id' => 'we_123',
                    'url' => $params['url'] ?? 'https://example.test/webhook',
                    'secret' => 'whsec_1234567890',
                    'status' => 'enabled',
                ];
            }
        };
        $stripe = (new TestStripeClient())->withService('webhookEndpoints', $spy);

        $command = new WebhookCommand($stripe, [
            'events' => ['invoice.paid', 'payment_intent.succeeded'],
        ]);
        $tester = new CommandTester($command);
        $tester->execute(['--url' => 'https://example.test/webhook']);

        $capturedParams = $spy::$capturedParams;
        self::assertIsArray($capturedParams);
        self::assertContains('customer.subscription.created', $capturedParams['enabled_events']);
        self::assertContains('customer.subscription.updated', $capturedParams['enabled_events']);
        self::assertContains('invoice.paid', $capturedParams['enabled_events']);
        self::assertContains('payment_intent.succeeded', $capturedParams['enabled_events']);
    }

    private function createStripeClient(): StripeClient
    {
        return (new TestStripeClient())->withService('webhookEndpoints', new class () {
            /**
             * @param array<string, mixed> $params
             */
            public function create(array $params): object
            {
                return (object) [
                    'id' => 'we_123',
                    'url' => $params['url'] ?? 'https://example.test/webhook',
                    'secret' => 'whsec_1234567890',
                    'status' => 'enabled',
                ];
            }
        });
    }
}
