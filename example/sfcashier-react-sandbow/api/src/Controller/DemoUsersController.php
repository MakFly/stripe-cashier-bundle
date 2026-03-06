<?php

declare(strict_types=1);

namespace App\Controller;

use App\Auth\DemoUsers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/auth')]
final class DemoUsersController extends AbstractController
{
    #[Route('/demo-users', name: 'api_auth_demo_users', methods: ['GET'])]
    public function list(Request $request, KernelInterface $kernel): JsonResponse
    {
        if ($kernel->getEnvironment() !== 'dev') {
            return new JsonResponse(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $withPassword = filter_var(
            $request->query->get('withPassword', '0'),
            FILTER_VALIDATE_BOOLEAN
        );

        $users = array_map(
            static function (array $user) use ($withPassword): array {
                return [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => $withPassword ? $user['password'] : null,
                ];
            },
            DemoUsers::all()
        );

        return new JsonResponse(['users' => $users]);
    }
}
