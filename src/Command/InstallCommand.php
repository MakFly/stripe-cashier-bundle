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

        $envFile = $input->getOption('env-file');
        $result = $this->installFileManager->install(
            $this->projectDir,
            is_string($envFile) ? $envFile : null,
        );

        $io->title('Cashier installation');
        $io->listing(array_merge(
            array_map(static fn (string $path): string => sprintf('created directory %s', $path), $result['directoriesCreated']),
            array_map(static fn (string $path): string => sprintf('skipped directory %s', $path), $result['directoriesSkipped']),
            array_map(static fn (string $path): string => sprintf('created %s', $path), $result['created']),
            array_map(static fn (string $path): string => sprintf('skipped %s', $path), $result['skipped']),
            array_map(static fn (string $name): string => sprintf('env added %s', $name), $result['envUpdated']),
        ));

        if ($result['created'] === [] && $result['directoriesCreated'] === [] && $result['envUpdated'] === []) {
            $io->success('Cashier is already installed. Nothing changed.');

            return Command::SUCCESS;
        }

        $io->success('Cashier configuration installed successfully.');
        $io->note('Review the generated Stripe keys and keep your billable entity implementing CashierBundle\\Contract\\BillableEntityInterface.');

        return Command::SUCCESS;
    }
}
