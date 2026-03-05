<?php

declare(strict_types=1);

namespace CashierBundle\Command;

use CashierBundle\Repository\SubscriptionItemRepository;
use Stripe\StripeClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'cashier:report-usage',
    description: 'Report usage for a metered subscription item',
)]
class ReportUsageCommand extends Command
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly SubscriptionItemRepository $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('subscription-item-id', InputArgument::REQUIRED, 'The subscription item ID (local)')
            ->addArgument('quantity', InputArgument::REQUIRED, 'The quantity to report')
            ->addOption('timestamp', 't', InputOption::VALUE_OPTIONAL, 'Unix timestamp for the usage')
            ->addOption('action', 'a', InputOption::VALUE_OPTIONAL, 'Usage action (increment, set)', 'increment')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $item = $this->repository->find($input->getArgument('subscription-item-id'));

        if (!$item) {
            $io->error('Subscription item not found');

            return Command::FAILURE;
        }

        $params = [
            'quantity' => (int) $input->getArgument('quantity'),
            'timestamp' => $input->getOption('timestamp') ?? time(),
            'action' => $input->getOption('action') ?: 'increment',
        ];

        $this->stripe->subscriptionItems->createUsageRecord($item->getStripeId(), $params);

        $io->success('Usage reported successfully!');

        return Command::SUCCESS;
    }
}
