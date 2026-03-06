<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Command;

use CashierBundle\Command\InstallCommand;
use CashierBundle\Service\InstallFileManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/** Test suite for InstallCommand. */
final class InstallCommandTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/cashier_install_cmd_' . bin2hex(random_bytes(8));
        mkdir($this->projectDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testInstallCommandCreatesFilesWithSelectedBillableClass(): void
    {
        $command = new InstallCommand(new InstallFileManager(), $this->projectDir);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('created directory var/data', $tester->getDisplay());
        self::assertStringContainsString('created directory var/data/invoices', $tester->getDisplay());
        self::assertStringContainsString('created config/packages/cashier.yaml', $tester->getDisplay());
        self::assertStringContainsString('created config/packages/cashier_doctrine.yaml', $tester->getDisplay());
        self::assertStringContainsString('created config/routes/cashier.yaml', $tester->getDisplay());
        self::assertDirectoryExists($this->projectDir . '/var/data/invoices');
        self::assertStringNotContainsString(
            'resolve_target_entities',
            (string) file_get_contents($this->projectDir . '/config/packages/cashier_doctrine.yaml'),
        );
    }

    public function testInstallCommandReportsNoChangesWhenReRun(): void
    {
        $command = new InstallCommand(new InstallFileManager(), $this->projectDir);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Cashier is already installed. Nothing changed.', $tester->getDisplay());
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
