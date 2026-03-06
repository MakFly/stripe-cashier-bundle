<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service;

use CashierBundle\Service\WebhookEnvFileManager;
use PHPUnit\Framework\TestCase;

/** Test suite for WebhookEnvFileManager. */
final class WebhookEnvFileManagerTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/cashier_env_' . bin2hex(random_bytes(8));
        mkdir($this->projectDir, 0777, true);
    }

    protected function tearDown(): void
    {
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

    public function testResolveTargetFileUsesEnvPriority(): void
    {
        file_put_contents($this->projectDir . '/.env.local', "FOO=1\n");
        file_put_contents($this->projectDir . '/.env.dev.local', "BAR=2\n");

        $manager = new WebhookEnvFileManager();

        $resolved = $manager->resolveTargetFile($this->projectDir, 'dev');

        self::assertSame($this->projectDir . '/.env.dev.local', $resolved);
    }

    public function testResolveTargetFileCreatesEnvLocalWhenNoFileExists(): void
    {
        $manager = new WebhookEnvFileManager();

        $resolved = $manager->resolveTargetFile($this->projectDir, 'test');

        self::assertSame($this->projectDir . '/.env.test.local', $resolved);
    }

    public function testWriteSecretReplacesExistingEntry(): void
    {
        $envFile = $this->projectDir . '/.env.local';
        file_put_contents($envFile, "APP_ENV=dev\nSTRIPE_WEBHOOK_SECRET=old_secret\n");

        $manager = new WebhookEnvFileManager();
        $manager->writeSecret($envFile, 'whsec_new');

        $content = (string) file_get_contents($envFile);

        self::assertStringContainsString("STRIPE_WEBHOOK_SECRET=whsec_new\n", $content);
        self::assertStringNotContainsString('old_secret', $content);
    }

    public function testWriteSecretAppendsEntryWhenMissing(): void
    {
        $envFile = $this->projectDir . '/.env';
        file_put_contents($envFile, "APP_ENV=dev\n");

        $manager = new WebhookEnvFileManager();
        $manager->writeSecret($envFile, 'whsec_value');

        $content = (string) file_get_contents($envFile);

        self::assertStringContainsString("APP_ENV=dev\nSTRIPE_WEBHOOK_SECRET=whsec_value\n", $content);
    }
}
