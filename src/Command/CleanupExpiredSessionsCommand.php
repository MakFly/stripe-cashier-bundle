<?php

declare(strict_types=1);

namespace CashierBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'cashier:cleanup-sessions',
    description: 'Cleanup expired checkout sessions',
)]
class CleanupExpiredSessionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('hours', 'H', InputOption::VALUE_OPTIONAL, 'Sessions older than this many hours', 24)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be deleted without actually deleting')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $hours = (int) $input->getOption('hours');
        $dryRun = $input->getOption('dry-run');

        $expiresAt = new \DateTimeImmutable("-{$hours} hours");

        // This would need a CheckoutSession entity and repository
        // For now, this is a placeholder implementation
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(s.id)')
            ->from('CashierBundle\Entity\CheckoutSession', 's')
            ->where('s.expiresAt < :expiresAt')
            ->andWhere('s.status IS NULL')
            ->setParameter('expiresAt', $expiresAt);

        try {
            $count = (int) $qb->getQuery()->getSingleScalarResult();

            if ($dryRun) {
                $io->note(sprintf('Would delete %d expired sessions (dry-run)', $count));

                return Command::SUCCESS;
            }

            if ($count === 0) {
                $io->success('No expired sessions to clean up');

                return Command::SUCCESS;
            }

            $qb = $this->entityManager->createQueryBuilder();
            $qb->delete('CashierBundle\Entity\CheckoutSession', 's')
                ->where('s.expiresAt < :expiresAt')
                ->andWhere('s.status IS NULL')
                ->setParameter('expiresAt', $expiresAt);

            $deleted = $qb->getQuery()->execute();

            $io->success(sprintf('Cleaned up %d expired sessions older than %d hours', $deleted, $hours));
        } catch (\Exception $e) {
            $io->error(sprintf('Error cleaning up sessions: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
