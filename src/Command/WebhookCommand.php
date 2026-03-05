<?php

declare(strict_types=1);

namespace CashierBundle\Command;

use Stripe\StripeClient;
use Stripe\Util\ApiVersion;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'cashier:webhook',
    description: 'Create a Stripe webhook endpoint',
)]
class WebhookCommand extends Command
{
    public const DEFAULT_EVENTS = [
        'customer.subscription.created',
        'customer.subscription.updated',
        'customer.subscription.deleted',
        'customer.updated',
        'customer.deleted',
        'payment_method.automatically_updated',
        'invoice.payment_action_required',
        'invoice.paid',
        'invoice.payment_failed',
        'checkout.session.completed',
    ];

    public function __construct(
        private readonly StripeClient $stripe,
        private readonly array $webhookConfig,
        private readonly string $cashierPath = 'cashier',
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('url', 'u', InputOption::VALUE_OPTIONAL, 'The webhook URL')
            ->addOption('disabled', 'd', InputOption::VALUE_NONE, 'Disable the webhook')
            ->addOption('api-version', null, InputOption::VALUE_OPTIONAL, 'Stripe API version')
            ->addOption('show-secret', null, InputOption::VALUE_NONE, 'Display webhook secret in clear text output')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $url = $input->getOption('url') ?? $this->guessUrl();
        $events = $this->webhookConfig['events'] ?? self::DEFAULT_EVENTS;

        $webhook = $this->stripe->webhookEndpoints->create([
            'url' => $url,
            'enabled_events' => $events,
            'api_version' => $input->getOption('api-version') ?? ApiVersion::CURRENT,
            'disabled' => $input->getOption('disabled'),
        ]);
        $maskedSecret = $this->maskSecret($webhook->secret);

        $io->success('Webhook endpoint created!');
        $io->table(
            ['Property', 'Value'],
            [
                ['ID', $webhook->id],
                ['URL', $webhook->url],
                ['Secret', $maskedSecret],
                ['Status', $webhook->status],
            ],
        );
        if ($input->getOption('show-secret')) {
            $io->note('Add this secret to your .env: STRIPE_WEBHOOK_SECRET=' . $webhook->secret);
        } else {
            $io->note('Secret is masked by default. Re-run with --show-secret to print it in clear text.');
        }

        return Command::SUCCESS;
    }

    private function guessUrl(): string
    {
        return sprintf('https://your-app.com/%s/webhook', trim($this->cashierPath, '/'));
    }

    private function maskSecret(string $secret): string
    {
        if (strlen($secret) <= 8) {
            return str_repeat('*', strlen($secret));
        }

        return substr($secret, 0, 4) . str_repeat('*', strlen($secret) - 8) . substr($secret, -4);
    }
}
