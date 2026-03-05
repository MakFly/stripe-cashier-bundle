<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service;

use CashierBundle\Service\InstallFileManager;
use PHPUnit\Framework\TestCase;

final class InstallFileManagerTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/cashier_install_' . bin2hex(random_bytes(8));
        mkdir($this->projectDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testInstallCreatesExpectedFilesAndEnvEntries(): void
    {
        $manager = new InstallFileManager();

        $result = $manager->install($this->projectDir, 'App\\Entity\\User');

        self::assertSame(
            [
                'config/packages/cashier.yaml',
                'config/packages/cashier_doctrine.yaml',
                'config/routes/cashier.yaml',
            ],
            $result['created'],
        );
        self::assertSame(['STRIPE_KEY', 'STRIPE_SECRET', 'STRIPE_WEBHOOK_SECRET'], $result['envUpdated']);
        self::assertFileExists($this->projectDir . '/config/packages/cashier.yaml');
        self::assertFileExists($this->projectDir . '/config/packages/cashier_doctrine.yaml');
        self::assertFileExists($this->projectDir . '/config/routes/cashier.yaml');
        self::assertStringContainsString(
            "CashierBundle\\Contract\\BillableEntityInterface: 'App\\Entity\\User'",
            (string) file_get_contents($this->projectDir . '/config/packages/cashier_doctrine.yaml'),
        );
        self::assertStringContainsString(
            'STRIPE_WEBHOOK_SECRET=whsec_change_me',
            (string) file_get_contents($this->projectDir . '/.env'),
        );
    }

    public function testInstallIsIdempotentAndDoesNotDuplicateEnvVars(): void
    {
        $manager = new InstallFileManager();

        $manager->install($this->projectDir, 'App\\Entity\\User');
        $result = $manager->install($this->projectDir, 'App\\Entity\\User');

        self::assertSame([], $result['created']);
        self::assertSame([], $result['envUpdated']);
        self::assertSame(
            [
                'config/packages/cashier.yaml',
                'config/packages/cashier_doctrine.yaml',
                'config/routes/cashier.yaml',
            ],
            $result['skipped'],
        );

        $envContent = (string) file_get_contents($this->projectDir . '/.env');
        self::assertSame(1, substr_count($envContent, 'STRIPE_KEY='));
        self::assertSame(1, substr_count($envContent, 'STRIPE_SECRET='));
        self::assertSame(1, substr_count($envContent, 'STRIPE_WEBHOOK_SECRET='));
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = $path . '/' . $entry;
            if (is_dir($entryPath)) {
                $this->removeDirectory($entryPath);
                continue;
            }

            unlink($entryPath);
        }

        rmdir($path);
    }
}
