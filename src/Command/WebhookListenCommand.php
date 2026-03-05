<?php

declare(strict_types=1);

namespace CashierBundle\Command;

use CashierBundle\Service\WebhookEnvFileManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'cashier:webhook:listen',
    description: 'Start Stripe CLI webhook listener and export STRIPE_WEBHOOK_SECRET',
)]
class WebhookListenCommand extends Command
{
    /**
     * @param array<string, mixed> $webhookConfig
     */
    public function __construct(
        private readonly array $webhookConfig,
        #[Autowire('%cashier.path%')]
        private readonly string $cashierPath,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly WebhookEnvFileManager $envFileManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('forward-to', null, InputOption::VALUE_OPTIONAL, 'Full webhook URL forwarded by Stripe CLI')
            ->addOption('base-url', null, InputOption::VALUE_OPTIONAL, 'Base URL used to build forward-to', 'http://127.0.0.1:8000')
            ->addOption('events', null, InputOption::VALUE_OPTIONAL, 'Comma-separated Stripe events to listen to')
            ->addOption('env-file', null, InputOption::VALUE_OPTIONAL, 'Target env file path (absolute or project-relative)')
            ->addOption('no-write-env', null, InputOption::VALUE_NONE, 'Do not write STRIPE_WEBHOOK_SECRET to any env file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->isStripeCliAvailable()) {
            $io->error('Stripe CLI not found. Install it first: https://docs.stripe.com/stripe-cli');

            return Command::FAILURE;
        }

        $forwardTo = $this->resolveForwardTo($input);
        $events = $this->resolveEvents((string) $input->getOption('events'));

        $command = ['stripe', 'listen', '--forward-to', $forwardTo];
        if ($events !== '') {
            $command[] = '--events';
            $command[] = $events;
        }

        $io->title('Stripe Webhook Listener');
        $io->writeln(sprintf('Forwarding to: <info>%s</info>', $forwardTo));
        if ($events !== '') {
            $io->writeln(sprintf('Events: <comment>%s</comment>', $events));
        }
        $io->newLine();

        [$exitCode, $secret] = $this->listen($command, $io);
        if ($exitCode !== 0) {
            $io->error(sprintf('Stripe CLI exited with code %d.', $exitCode));

            return Command::FAILURE;
        }

        if ($secret === null) {
            $io->warning('No webhook secret found in Stripe CLI output.');

            return Command::SUCCESS;
        }

        $io->newLine();
        $io->success('Webhook secret detected');
        $io->writeln(sprintf('STRIPE_WEBHOOK_SECRET=%s', $secret));

        if ((bool) $input->getOption('no-write-env')) {
            $io->note('Skipping env file update (--no-write-env).');

            return Command::SUCCESS;
        }

        $forcedEnvFile = $input->getOption('env-file');
        $envFile = $this->envFileManager->resolveTargetFile(
            $this->projectDir,
            $this->resolveAppEnv(),
            is_string($forcedEnvFile) ? $forcedEnvFile : null,
        );
        $this->envFileManager->writeSecret($envFile, $secret);

        $io->success(sprintf('Updated %s', $envFile));

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $command
     *
     * @return array{int, string|null}
     */
    protected function listen(array $command, SymfonyStyle $io): array
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, $this->projectDir);
        if (!is_resource($process)) {
            throw new \RuntimeException('Unable to start Stripe CLI process.');
        }

        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $secret = null;
        while (true) {
            $status = proc_get_status($process);
            $running = $status['running'];

            $read = [];
            if (is_resource($pipes[1])) {
                $read[] = $pipes[1];
            }
            if (is_resource($pipes[2])) {
                $read[] = $pipes[2];
            }

            if ($read === []) {
                break;
            }

            $write = null;
            $except = null;
            @stream_select($read, $write, $except, 0, 200000);

            foreach ($read as $stream) {
                $chunk = stream_get_contents($stream);
                if ($chunk === false || $chunk === '') {
                    continue;
                }

                $this->writeStyledChunk($io, $chunk);
                $secretFromChunk = $this->extractSecret($chunk);
                if ($secretFromChunk !== null) {
                    $secret = $secretFromChunk;
                }
            }

            if (!$running) {
                break;
            }
        }

        foreach ([1, 2] as $index) {
            if (isset($pipes[$index]) && is_resource($pipes[$index])) {
                $remaining = stream_get_contents($pipes[$index]);
                if (is_string($remaining) && $remaining !== '') {
                    $this->writeStyledChunk($io, $remaining);
                    $secretFromChunk = $this->extractSecret($remaining);
                    if ($secretFromChunk !== null) {
                        $secret = $secretFromChunk;
                    }
                }

                fclose($pipes[$index]);
            }
        }

        $exitCode = proc_close($process);

        return [$exitCode, $secret];
    }

    private function resolveForwardTo(InputInterface $input): string
    {
        $forwardTo = $input->getOption('forward-to');
        if (is_string($forwardTo) && trim($forwardTo) !== '') {
            return trim($forwardTo);
        }

        $baseUrl = (string) $input->getOption('base-url');
        $normalizedBaseUrl = rtrim(trim($baseUrl), '/');
        $path = '/' . trim($this->cashierPath, '/') . '/webhook';

        return $normalizedBaseUrl . $path;
    }

    private function resolveEvents(string $eventsOption): string
    {
        if (trim($eventsOption) !== '') {
            $events = array_values(array_filter(array_map(
                static fn (string $event): string => trim($event),
                explode(',', $eventsOption),
            )));

            return implode(',', $events);
        }

        $events = $this->webhookConfig['events'] ?? [];
        if (!is_array($events) || $events === []) {
            return '';
        }

        $normalized = array_values(array_filter(array_map(
            static fn (mixed $event): string => is_string($event) ? trim($event) : '',
            $events,
        )));

        return implode(',', $normalized);
    }

    protected function writeStyledChunk(SymfonyStyle $io, string $chunk): void
    {
        foreach (preg_split("/(\r\n|\n|\r)/", $chunk, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [] as $part) {
            if ($part === '') {
                continue;
            }

            if ($part === "\n" || $part === "\r" || $part === "\r\n") {
                $io->write($part);

                continue;
            }

            $io->write($this->styleLine($part));
        }
    }

    protected function styleLine(string $line): string
    {
        $escaped = OutputFormatter::escape($line);

        if (preg_match('/<--\s+\[(\d{3})\]/', $line, $matches) === 1) {
            $statusCode = (int) $matches[1];

            return match (true) {
                $statusCode >= 500 => sprintf('<error>%s</error>', $escaped),
                $statusCode >= 400 => sprintf('<fg=yellow;options=bold>%s</>', $escaped),
                $statusCode >= 300 => sprintf('<fg=magenta;options=bold>%s</>', $escaped),
                default => sprintf('<fg=green;options=bold>%s</>', $escaped),
            };
        }

        if (str_contains($line, '-->')) {
            return sprintf('<fg=cyan>%s</>', $escaped);
        }

        if (str_contains($line, 'level=error') || str_contains($line, 'Error when writing ping message')) {
            return sprintf('<fg=red;options=bold>%s</>', $escaped);
        }

        if (str_contains($line, 'A newer version of the Stripe CLI is available')) {
            return sprintf('<fg=yellow>%s</>', $escaped);
        }

        if (str_contains($line, 'Ready!')) {
            return sprintf('<fg=green>%s</>', $escaped);
        }

        return $escaped;
    }

    private function extractSecret(string $chunk): ?string
    {
        if (preg_match('/whsec_[A-Za-z0-9]+/', $chunk, $matches) === 1) {
            return $matches[0];
        }

        return null;
    }

    private function resolveAppEnv(): string
    {
        $env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV');
        if (!is_string($env) || trim($env) === '') {
            return 'dev';
        }

        return trim($env);
    }

    protected function isStripeCliAvailable(): bool
    {
        $path = getenv('PATH');
        if (!is_string($path) || trim($path) === '') {
            return false;
        }

        $isWindows = DIRECTORY_SEPARATOR === '\\';
        $extensions = [''];

        if ($isWindows) {
            $pathExt = getenv('PATHEXT');
            if (!is_string($pathExt) || trim($pathExt) === '') {
                $pathExt = '.EXE;.BAT;.CMD;.COM';
            }

            $extensions = array_values(array_filter(array_map(
                static fn (string $ext): string => strtoupper(trim($ext)),
                explode(';', $pathExt),
            )));
        }

        foreach (explode(PATH_SEPARATOR, $path) as $directory) {
            $directory = trim($directory, " \t\n\r\0\x0B\"'");
            if ($directory === '') {
                continue;
            }

            if ($isWindows) {
                foreach ($extensions as $extension) {
                    $candidate = $directory . DIRECTORY_SEPARATOR . 'stripe' . $extension;
                    if (is_file($candidate)) {
                        return true;
                    }
                }

                continue;
            }

            $candidate = $directory . DIRECTORY_SEPARATOR . 'stripe';
            if (is_file($candidate) && is_executable($candidate)) {
                return true;
            }
        }

        return false;
    }
}
