<?php

declare(strict_types=1);

namespace CashierBundle\Service;

final class WebhookEnvFileManager
{
    public function resolveTargetFile(string $projectDir, ?string $appEnv = null, ?string $forcedFile = null): string
    {
        if ($forcedFile !== null && trim($forcedFile) !== '') {
            if ($this->isAbsolutePath($forcedFile)) {
                return $forcedFile;
            }

            return rtrim($projectDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($forcedFile, DIRECTORY_SEPARATOR);
        }

        $env = $appEnv ?? $this->resolveAppEnv();

        $candidates = [
            sprintf('.env.%s.local', $env),
            '.env.local',
            sprintf('.env.%s', $env),
            '.env',
        ];

        foreach ($candidates as $candidate) {
            $path = rtrim($projectDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $candidate;
            if (is_file($path)) {
                return $path;
            }
        }

        return rtrim($projectDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . sprintf('.env.%s.local', $env);
    }

    public function writeSecret(string $envFile, string $secret): void
    {
        $line = sprintf('STRIPE_WEBHOOK_SECRET=%s', $secret);
        $content = is_file($envFile) ? (string) file_get_contents($envFile) : '';

        if (preg_match('/^STRIPE_WEBHOOK_SECRET=.*/m', $content) === 1) {
            $updated = preg_replace('/^STRIPE_WEBHOOK_SECRET=.*/m', $line, $content);
            if ($updated === null) {
                throw new \RuntimeException(sprintf('Failed to update webhook secret in "%s".', $envFile));
            }

            file_put_contents($envFile, $updated);

            return;
        }

        if ($content !== '' && !str_ends_with($content, "\n")) {
            $content .= "\n";
        }

        file_put_contents($envFile, $content . $line . "\n");
    }

    private function resolveAppEnv(): string
    {
        $env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV');
        if (!is_string($env) || trim($env) === '') {
            return 'dev';
        }

        return trim($env);
    }

    private function isAbsolutePath(string $path): bool
    {
        if (str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return true;
        }

        return preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}
