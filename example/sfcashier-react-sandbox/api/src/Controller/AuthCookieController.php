<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\AuthCookieManager;
use BetterAuth\Core\AuthManager;
use BetterAuth\Core\Exceptions\RateLimitException;
use BetterAuth\Core\Entities\User as BetterAuthUser;
use BetterAuth\Core\Interfaces\UserRepositoryInterface;
use BetterAuth\Core\PasswordHasher;
use BetterAuth\Providers\MagicLinkProvider\MagicLinkProvider;
use BetterAuth\Providers\TotpProvider\TotpProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/auth')]
final class AuthCookieController extends AbstractController
{
    public function __construct(
        private readonly AuthManager $authManager,
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasher $passwordHasher,
        private readonly TotpProvider $totpProvider,
        private readonly MagicLinkProvider $magicLinkProvider,
        private readonly AuthCookieManager $authCookieManager,
        #[Autowire(env: 'FRONTEND_URL')]
        private readonly string $frontendUrl = 'http://localhost:5173',
        #[Autowire('%better_auth.token%')]
        private readonly array $tokenConfig = [],
    ) {
    }

    #[Route('/register', name: 'app_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $name = $data['name'] ?? null;

        if (!is_string($email) || !is_string($password)) {
            return $this->json(['error' => 'Email and password are required'], 400);
        }

        try {
            $this->authManager->signUp(
                $email,
                $password,
                is_string($name) ? ['name' => $name] : [],
            );

            $result = $this->authManager->signIn(
                $email,
                $password,
                $request->getClientIp() ?? '127.0.0.1',
                $request->headers->get('User-Agent') ?? 'Unknown',
            );

            return $this->buildAuthSuccessResponse($request, $result);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Registration failed'], 400);
        }
    }

    #[Route('/login', name: 'app_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!is_string($email) || !is_string($password)) {
            return $this->json(['error' => 'Email and password are required'], 400);
        }

        try {
            $user = $this->userRepository->findByEmail($email);
            if ($user === null || !$user->hasPassword()) {
                return $this->json(['error' => 'Invalid credentials'], 401);
            }

            $passwordHash = $user->getPassword();
            if ($passwordHash === null || !$this->passwordHasher->verify($password, $passwordHash)) {
                return $this->json(['error' => 'Invalid credentials'], 401);
            }

            if ($this->totpProvider->requires2fa((string) $user->getId())) {
                return $this->json([
                    'requires2fa' => true,
                    'message' => 'Two-factor authentication required',
                ]);
            }

            $result = $this->authManager->signIn(
                $email,
                $password,
                $request->getClientIp() ?? '127.0.0.1',
                $request->headers->get('User-Agent') ?? 'Unknown',
            );

            return $this->buildAuthSuccessResponse($request, $result);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Authentication failed'], 401);
        }
    }

    #[Route('/login/2fa', name: 'app_auth_login_2fa', methods: ['POST'])]
    public function login2fa(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $code = $data['code'] ?? null;

        if (!is_string($email) || !is_string($password) || !is_string($code)) {
            return $this->json(['error' => 'Email, password and code are required'], 400);
        }

        try {
            $user = $this->userRepository->findByEmail($email);
            if ($user === null || !$user->hasPassword()) {
                return $this->json(['error' => 'Invalid credentials'], 401);
            }

            $passwordHash = $user->getPassword();
            if ($passwordHash === null || !$this->passwordHasher->verify($password, $passwordHash)) {
                return $this->json(['error' => 'Invalid credentials'], 401);
            }

            $verified = $this->totpProvider->verify((string) $user->getId(), $code);
            if (!$verified) {
                return $this->json(['error' => 'Invalid 2FA code'], 401);
            }

            $result = $this->authManager->signIn(
                $email,
                $password,
                $request->getClientIp() ?? '127.0.0.1',
                $request->headers->get('User-Agent') ?? 'Unknown',
            );

            return $this->buildAuthSuccessResponse($request, $result);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Authentication failed'], 401);
        }
    }

    #[Route('/magic-link/verify', name: 'app_auth_magic_link_verify', methods: ['POST'])]
    public function verifyMagicLink(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $token = $data['token'] ?? null;

        if (!is_string($token) || $token === '') {
            return $this->json(['error' => 'Magic link token is required'], 400);
        }

        try {
            $result = $this->magicLinkProvider->verifyMagicLink(
                $token,
                $request->getClientIp() ?? '127.0.0.1',
                $request->headers->get('User-Agent') ?? 'Unknown',
            );

            if (!($result['success'] ?? false)) {
                return $this->json([
                    'error' => $result['error'] ?? 'Invalid or expired magic link',
                ], 400);
            }

            return $this->buildAuthSuccessResponse($request, $result);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Magic link verification failed'], 400);
        }
    }

    #[Route('/magic-link/send', name: 'app_auth_magic_link_send', methods: ['POST'])]
    public function sendMagicLink(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $email = $data['email'] ?? null;

        if (!is_string($email) || $email === '') {
            return $this->json(['error' => 'Email is required'], 400);
        }

        $callbackUrl = $data['callbackUrl'] ?? rtrim($this->frontendUrl, '/') . '/auth/magic-link/verify';
        if (!is_string($callbackUrl) || $callbackUrl === '') {
            $callbackUrl = rtrim($this->frontendUrl, '/') . '/auth/magic-link/verify';
        }

        try {
            $result = $this->magicLinkProvider->sendMagicLink(
                $email,
                $request->getClientIp() ?? '127.0.0.1',
                $request->headers->get('User-Agent') ?? 'Unknown',
                $callbackUrl,
            );

            return $this->json([
                'message' => 'Magic link sent successfully',
                'expiresIn' => $result['expiresIn'] ?? 600,
            ]);
        } catch (RateLimitException $e) {
            return $this->json([
                'error' => 'Too many requests. Please try again later.',
                'retryAfter' => $e->retryAfter,
            ], 429);
        } catch (TransportExceptionInterface) {
            return $this->json([
                'error' => 'Failed to send email. Please check mailer configuration.',
            ], 500);
        } catch (\Throwable) {
            return $this->json(['error' => 'Failed to send magic link'], 500);
        }
    }

    #[Route('/me', name: 'app_auth_me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $token = $this->extractAuthToken($request);
        if ($token === null) {
            return $this->json(['error' => 'No token provided'], 401);
        }

        try {
            $user = $this->authManager->getCurrentUser($token);
            if (!$user instanceof BetterAuthUser) {
                return $this->json(['error' => 'Invalid token'], 401);
            }

            return $this->json($this->formatUser($user));
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Authentication failed'], 401);
        }
    }

    #[Route('/refresh', name: 'app_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = [];
        }
        $refreshToken = $data['refreshToken'] ?? $request->cookies->get(AuthCookieManager::REFRESH_COOKIE);
        if (!is_string($refreshToken) || $refreshToken === '') {
            return $this->json(['error' => 'Refresh token is required'], 400);
        }

        try {
            $result = $this->authManager->refresh($refreshToken);
            $response = $this->json($result);
            $this->attachCookiesFromResult($response, $request, $result);

            return $response;
        } catch (\Throwable $e) {
            $response = $this->json(['error' => 'Token refresh failed'], 401);
            $this->authCookieManager->clearAuthCookies($response, $request);

            return $response;
        }
    }

    #[Route('/2fa/setup', name: 'app_auth_2fa_setup', methods: ['POST'])]
    public function setupTwoFactor(Request $request): JsonResponse
    {
        $token = $this->extractAuthToken($request);
        if ($token === null) {
            return $this->json(['error' => 'No token provided'], 401);
        }

        try {
            $user = $this->authManager->getCurrentUser($token);
            if (!$user instanceof BetterAuthUser) {
                return $this->json(['error' => 'Invalid token'], 401);
            }

            $result = $this->totpProvider->generateSecret((string) $user->getId(), $user->getEmail());

            return $this->json([
                'secret' => $result['secret'] ?? null,
                'qrCode' => $result['qrCode'] ?? null,
                'manualEntryKey' => $result['manualEntryKey'] ?? ($result['secret'] ?? null),
                'backupCodes' => is_array($result['backupCodes'] ?? null) ? $result['backupCodes'] : [],
            ]);
        } catch (\Throwable) {
            return $this->json(['error' => 'Two-factor setup failed'], 400);
        }
    }

    #[Route('/2fa/validate', name: 'app_auth_2fa_validate', methods: ['POST'])]
    public function validateTwoFactor(Request $request): JsonResponse
    {
        $token = $this->extractAuthToken($request);
        if ($token === null) {
            return $this->json(['error' => 'No token provided'], 401);
        }

        $data = $request->toArray();
        $code = $data['code'] ?? null;
        if (!is_string($code) || $code === '') {
            return $this->json(['error' => 'Verification code is required'], 400);
        }

        try {
            $user = $this->authManager->getCurrentUser($token);
            if (!$user instanceof BetterAuthUser) {
                return $this->json(['error' => 'Invalid token'], 401);
            }

            $verified = $this->totpProvider->verifyAndEnable((string) $user->getId(), $code);
            if (!$verified) {
                return $this->json(['error' => 'Invalid verification code'], 400);
            }

            return $this->json([
                'message' => 'Two-factor authentication enabled successfully',
                'enabled' => true,
            ]);
        } catch (\Throwable) {
            return $this->json(['error' => 'Two-factor validation failed'], 400);
        }
    }

    #[Route('/logout', name: 'app_auth_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $token = $this->extractAuthToken($request);
        if ($token !== null) {
            try {
                $this->authManager->signOut($token);
            } catch (\Throwable) {
                // Best effort logout
            }
        }

        $response = $this->json(['message' => 'Logged out successfully']);
        $this->authCookieManager->clearAuthCookies($response, $request);

        return $response;
    }

    /**
     * @param array<string, mixed> $result
     */
    private function buildAuthSuccessResponse(Request $request, array $result): JsonResponse
    {
        $response = $this->json([
            'access_token' => $result['access_token'] ?? null,
            'refresh_token' => $result['refresh_token'] ?? null,
            'expires_in' => $result['expires_in'] ?? ($this->tokenConfig['lifetime'] ?? 3600),
            'token_type' => $result['token_type'] ?? 'Bearer',
            'user' => is_array($result['user'] ?? null) ? $result['user'] : null,
        ]);

        $this->attachCookiesFromResult($response, $request, $result);

        return $response;
    }

    /**
     * @param array<string, mixed> $result
     */
    private function attachCookiesFromResult(JsonResponse $response, Request $request, array $result): void
    {
        $accessToken = $result['access_token'] ?? null;
        $refreshToken = $result['refresh_token'] ?? null;
        if (!is_string($accessToken) || !is_string($refreshToken) || $accessToken === '' || $refreshToken === '') {
            return;
        }

        $this->authCookieManager->attachAuthCookies(
            $response,
            $request,
            $accessToken,
            $refreshToken,
            (int) ($result['expires_in'] ?? ($this->tokenConfig['lifetime'] ?? 3600)),
            (int) ($this->tokenConfig['refresh_lifetime'] ?? 2592000),
        );
    }

    private function extractAuthToken(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');
        if (is_string($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        $cookieToken = $request->cookies->get(AuthCookieManager::ACCESS_COOKIE);
        return is_string($cookieToken) && $cookieToken !== '' ? $cookieToken : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUser(BetterAuthUser $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'username' => $user->getUsername(),
            'avatar' => $user->getAvatar(),
            'emailVerified' => $user->isEmailVerified(),
            'emailVerifiedAt' => $user->getEmailVerifiedAt()?->format(\DateTimeInterface::ATOM),
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $user->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'metadata' => $user->getMetadata(),
        ];
    }
}
