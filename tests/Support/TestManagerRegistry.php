<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Support;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;

final readonly class TestManagerRegistry implements ManagerRegistry
{
    public function __construct(
        private ObjectManager $manager,
        private Connection $connection,
    ) {
    }

    public function getDefaultManagerName(): string
    {
        return 'default';
    }

    public function getManager(string|null $name = null): ObjectManager
    {
        return $this->manager;
    }

    public function getManagers(): array
    {
        return ['default' => $this->manager];
    }

    public function resetManager(string|null $name = null): ObjectManager
    {
        return $this->manager;
    }

    public function getManagerNames(): array
    {
        return ['default' => 'default'];
    }

    public function getRepository(string $persistentObject, string|null $persistentManagerName = null): ObjectRepository
    {
        return $this->manager->getRepository($persistentObject);
    }

    public function getManagerForClass(string $class): ObjectManager
    {
        return $this->manager;
    }

    public function getDefaultConnectionName(): string
    {
        return 'default';
    }

    public function getConnection(string|null $name = null): Connection
    {
        return $this->connection;
    }

    public function getConnections(): array
    {
        return ['default' => $this->connection];
    }

    public function getConnectionNames(): array
    {
        return ['default' => 'default'];
    }
}
