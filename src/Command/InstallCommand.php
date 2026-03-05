<?php

declare(strict_types=1);

namespace CashierBundle\Command;

use CashierBundle\Service\InstallFileManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'cashier:install',
    description: 'Install Cashier configuration files into the host Symfony application',
)]
final class InstallCommand extends Command
{
    public function __construct(
        private readonly InstallFileManager $installFileManager,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'billable-class',
                null,
                InputOption::VALUE_OPTIONAL,
                'Fully-qualified billable entity class used for ResolveTargetEntity',
                'App\\Entity\\User',
            )
            ->addOption(
                'env-file',
                null,
                InputOption::VALUE_OPTIONAL,
                'Target env file path (absolute or project-relative)',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $billableClass = (string) $input->getOption('billable-class');
        if ($billableClass === '') {
            $io->error('The --billable-class option cannot be empty.');

            return Command::INVALID;
        }

        $envFile = $input->getOption('env-file');
        $result = $this->installFileManager->install(
            $this->projectDir,
            $billableClass,
            is_string($envFile) ? $envFile : null,
        );

        $io->title('Cashier installation');
        $io->listing(array_merge(
            array_map(static fn (string $path): string => sprintf('created %s', $path), $result['created']),
            array_map(static fn (string $path): string => sprintf('skipped %s', $path), $result['skipped']),
            array_map(static fn (string $name): string => sprintf('env added %s', $name), $result['envUpdated']),
        ));

        if ($result['created'] === [] && $result['envUpdated'] === []) {
            $io->success('Cashier is already installed. Nothing changed.');

            return Command::SUCCESS;
        }

        $io->success('Cashier configuration installed successfully.');
        $io->note('Review the generated Stripe keys and adjust the billable class mapping if needed.');

        return Command::SUCCESS;
    }
}
