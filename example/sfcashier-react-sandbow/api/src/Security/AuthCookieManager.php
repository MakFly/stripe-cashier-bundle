<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthCookieManager
{
    public const ACCESS_COOKIE = 'access_token';
    public const REFRESH_COOKIE = 'refresh_token';
    public const AUTH_HINT_COOKIE = 'auth_hint';

    public function attachAuthCookies(
        Response $response,
        Request $request,
        string $accessToken,
        string $refreshToken,
        int $accessTtl,
        int $refreshTtl,
    ): void {
        $secure = $this->isSecureContext($request);

        $response->headers->setCookie(
            Cookie::create(
                name: self::ACCESS_COOKIE,
                value: $accessToken,
                expire: time() + max(1, $accessTtl),
                path: '/',
                secure: $secure,
                httpOnly: true,
                sameSite: Cookie::SAMESITE_LAX,
            ),
        );

        $response->headers->setCookie(
            Cookie::create(
                name: self::REFRESH_COOKIE,
                value: $refreshToken,
                expire: time() + max(1, $refreshTtl),
                path: '/',
                secure: $secure,
                httpOnly: true,
                sameSite: Cookie::SAMESITE_LAX,
            ),
        );

        $response->headers->setCookie(
            Cookie::create(
                name: self::AUTH_HINT_COOKIE,
                value: '1',
                expire: time() + max(1, $refreshTtl),
                path: '/',
                secure: $secure,
                httpOnly: false,
                sameSite: Cookie::SAMESITE_LAX,
            ),
        );
    }

    public function clearAuthCookies(Response $response, Request $request): void
    {
        $secure = $this->isSecureContext($request);

        foreach ([self::ACCESS_COOKIE, self::REFRESH_COOKIE, self::AUTH_HINT_COOKIE] as $name) {
            $response->headers->setCookie(
                Cookie::create(
                    name: $name,
                    value: '',
                    expire: 1,
                    path: '/',
                    secure: $secure,
                    httpOnly: $name !== self::AUTH_HINT_COOKIE,
                    sameSite: Cookie::SAMESITE_LAX,
                ),
            );
        }
    }

    private function isSecureContext(Request $request): bool
    {
        return $request->isSecure()
            || in_array($request->getHost(), ['localhost', '127.0.0.1'], true);
    }
}
