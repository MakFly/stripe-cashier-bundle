<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use Symfony\Component\Filesystem\Filesystem;

final class InstallFileManager
{
    private const SKELETON_ROOT = __DIR__ . '/../Resources/skeleton';

    private const ENV_VARS = [
        'STRIPE_KEY' => 'pk_test_change_me',
        'STRIPE_SECRET' => 'sk_test_change_me',
        'STRIPE_WEBHOOK_SECRET' => 'whsec_change_me',
    ];

    public function __construct(
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * @return array{created: list<string>, skipped: list<string>, envUpdated: list<string>}
     */
    public function install(string $projectDir, ?string $envFile = null): array
    {
        $created = [];
        $skipped = [];

        $files = [
            'config/packages/cashier.yaml' => [
                'template' => self::SKELETON_ROOT . '/config/packages/cashier.yaml.tpl',
                'context' => [],
            ],
            'config/packages/cashier_doctrine.yaml' => [
                'template' => self::SKELETON_ROOT . '/config/packages/cashier_doctrine.yaml.tpl',
                'context' => [],
            ],
            'config/routes/cashier.yaml' => [
                'template' => self::SKELETON_ROOT . '/config/routes/cashier.yaml.tpl',
                'context' => [],
            ],
        ];

        foreach ($files as $relativePath => $definition) {
            $targetPath = rtrim($projectDir, '/') . '/' . $relativePath;
            $this->filesystem->mkdir(\dirname($targetPath));

            if ($this->filesystem->exists($targetPath)) {
                $skipped[] = $relativePath;
                continue;
            }

            $contents = $this->renderTemplate($definition['template'], $definition['context']);
            $this->filesystem->dumpFile($targetPath, $contents);
            $created[] = $relativePath;
        }

        $envUpdated = $this->updateEnvFile($projectDir, $envFile);

        return [
            'created' => $created,
            'skipped' => $skipped,
            'envUpdated' => $envUpdated,
        ];
    }

    /**
     * @return list<string>
     */
    public function updateEnvFile(string $projectDir, ?string $envFile = null): array
    {
        $resolvedEnvPath = $this->resolveEnvFile($projectDir, $envFile);
        $this->filesystem->mkdir(\dirname($resolvedEnvPath));

        $content = $this->filesystem->exists($resolvedEnvPath)
            ? (string) file_get_contents($resolvedEnvPath)
            : '';

        $updated = [];
        $appendedLines = [];

        foreach (self::ENV_VARS as $name => $defaultValue) {
            if (preg_match('/^' . preg_quote($name, '/') . '=/m', $content) === 1) {
                continue;
            }

            $appendedLines[] = sprintf('%s=%s', $name, $defaultValue);
            $updated[] = $name;
        }

        if ($appendedLines === []) {
            return [];
        }

        $prefix = $content === '' ? '' : rtrim($content) . PHP_EOL . PHP_EOL;
        $block = '# Stripe Cashier' . PHP_EOL . implode(PHP_EOL, $appendedLines) . PHP_EOL;
        $this->filesystem->dumpFile($resolvedEnvPath, $prefix . $block);

        return $updated;
    }

    private function resolveEnvFile(string $projectDir, ?string $envFile = null): string
    {
        if (is_string($envFile) && $envFile !== '') {
            if (str_starts_with($envFile, '/')) {
                return $envFile;
            }

            return rtrim($projectDir, '/') . '/' . ltrim($envFile, '/');
        }

        return rtrim($projectDir, '/') . '/.env';
    }

    /**
     * @param array<string, string> $context
     */
    private function renderTemplate(string $templatePath, array $context): string
    {
        $contents = (string) file_get_contents($templatePath);

        return strtr($contents, $context);
    }
}
