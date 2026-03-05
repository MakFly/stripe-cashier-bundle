<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Command;

use CashierBundle\Command\WebhookListenCommand;
use CashierBundle\Service\WebhookEnvFileManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

final class WebhookListenCommandTest extends TestCase
{
    private string $projectDir;
    private string $previousAppEnv;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/cashier_listen_' . bin2hex(random_bytes(8));
        mkdir($this->projectDir, 0777, true);

        $this->previousAppEnv = getenv('APP_ENV') ?: '';
        putenv('APP_ENV=dev');
    }

    protected function tearDown(): void
    {
        if ($this->previousAppEnv !== '') {
            putenv('APP_ENV=' . $this->previousAppEnv);
        } else {
            putenv('APP_ENV');
        }

        $entries = glob($this->projectDir . '/{,.}*', GLOB_BRACE);
        if (is_array($entries)) {
            foreach ($entries as $entry) {
                $basename = basename($entry);
                if ($basename === '.' || $basename === '..') {
                    continue;
                }

                if (is_file($entry)) {
                    unlink($entry);
                }
            }
        }

        if (is_dir($this->projectDir)) {
            rmdir($this->projectDir);
        }
    }

    public function testDefaultForwardToAndEnvWrite(): void
    {
        $command = new class (['events' => []], 'stripe', $this->projectDir, new WebhookEnvFileManager()) extends WebhookListenCommand {
            /** @var list<string> */
            public array $capturedCommand = [];

            protected function listen(array $command, SymfonyStyle $io): array
            {
                $this->capturedCommand = $command;
                $io->write("Ready! Your webhook signing secret is whsec_abc123\n");

                return [0, 'whsec_abc123'];
            }

            protected function isStripeCliAvailable(): bool
            {
                return true;
            }
        };

        $tester = new CommandTester($command);
        $exit = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exit);
        self::assertContains('http://127.0.0.1:8000/stripe/webhook', $command->capturedCommand);
        self::assertFileExists($this->projectDir . '/.env.dev.local');
        self::assertStringContainsString(
            'STRIPE_WEBHOOK_SECRET=whsec_abc123',
            (string) file_get_contents($this->projectDir . '/.env.dev.local'),
        );
        self::assertStringContainsString('STRIPE_WEBHOOK_SECRET=whsec_abc123', $tester->getDisplay());
    }

    public function testNoWriteEnvDoesNotCreateFile(): void
    {
        $command = new class (['events' => []], 'stripe', $this->projectDir, new WebhookEnvFileManager()) extends WebhookListenCommand {
            protected function listen(array $command, SymfonyStyle $io): array
            {
                return [0, 'whsec_nowrite'];
            }

            protected function isStripeCliAvailable(): bool
            {
                return true;
            }
        };

        $tester = new CommandTester($command);
        $exit = $tester->execute(['--no-write-env' => true]);

        self::assertSame(Command::SUCCESS, $exit);
        self::assertFileDoesNotExist($this->projectDir . '/.env.dev.local');
    }

    public function testForwardToAndEventsOptionsAreApplied(): void
    {
        $command = new class (['events' => []], 'stripe', $this->projectDir, new WebhookEnvFileManager()) extends WebhookListenCommand {
            /** @var list<string> */
            public array $capturedCommand = [];

            protected function listen(array $command, SymfonyStyle $io): array
            {
                $this->capturedCommand = $command;

                return [0, 'whsec_events'];
            }

            protected function isStripeCliAvailable(): bool
            {
                return true;
            }
        };

        $tester = new CommandTester($command);
        $exit = $tester->execute([
            '--forward-to' => 'https://example.test/webhook',
            '--events' => 'invoice.paid, customer.updated ',
            '--no-write-env' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exit);
        self::assertSame(
            ['stripe', 'listen', '--forward-to', 'https://example.test/webhook', '--events', 'invoice.paid,customer.updated'],
            $command->capturedCommand,
        );
    }

    public function testFailsWhenStripeCliMissing(): void
    {
        $command = new class (['events' => []], 'stripe', $this->projectDir, new WebhookEnvFileManager()) extends WebhookListenCommand {
            protected function isStripeCliAvailable(): bool
            {
                return false;
            }
        };

        $tester = new CommandTester($command);
        $exit = $tester->execute([]);

        self::assertSame(Command::FAILURE, $exit);
        self::assertStringContainsString('Stripe CLI not found', $tester->getDisplay());
    }
}
