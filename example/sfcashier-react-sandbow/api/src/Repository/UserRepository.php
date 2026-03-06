<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function createOrUpdate(string $email, string $name): User
    {
        $user = $this->findByEmail($email);

        if ($user === null) {
            $user = new User();
            $user->setEmail($email);
            $user->setName($name);
            $user->setPassword(password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT));
            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();
        }

        return $user;
    }
}
