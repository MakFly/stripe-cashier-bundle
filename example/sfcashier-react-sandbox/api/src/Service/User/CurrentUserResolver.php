<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CurrentUserResolver
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function resolve(SessionInterface $session, UserInterface|null $authenticatedUser): ?User
    {
        if ($authenticatedUser instanceof User) {
            return $authenticatedUser;
        }

        $userId = $session->get('user_id');
        if ($userId === null) {
            return null;
        }

        return $this->userRepository->find($userId);
    }
}
