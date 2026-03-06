<?php

declare(strict_types=1);

namespace App\State;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class AuthQuickLoginProcessor
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function process(Request $request, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if ($email === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException('Invalid email');
        }

        $name = $data['name'] ?? explode('@', $email)[0];
        $name = ucfirst($name);

        $user = $this->userRepository->createOrUpdate($email, $name);

        $session->set('user_id', $user->getId());

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'createdAt' => $user->getCreatedAt()->format(\DateTime::ATOM),
        ]);
    }
}
