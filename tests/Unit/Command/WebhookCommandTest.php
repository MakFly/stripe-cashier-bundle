<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Command;

use CashierBundle\Command\WebhookCommand;
use CashierBundle\Tests\Support\TestStripeClient;
use PHPUnit\Framework\TestCase;
use Stripe\StripeClient;
use Symfony\Component\Console\Tester\CommandTester;

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
