<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class GuestCartCookieService
{
    private const COOKIE_NAME = 'sfcashier_guest_cart';
    private const COOKIE_TTL = 2_592_000; // 30 days
    private const MAX_ITEMS = 25;
    private const MAX_QUANTITY = 99;
    private const VERSION = 1;

    public function __construct(
        #[Autowire('%env(BETTER_AUTH_SECRET)%')]
        private readonly string $secret,
    ) {
    }

    /**
     * @return array{items: list<array{productId: int, quantity: int}>, invalid: bool}
     */
    public function decodeFromRequest(Request $request): array
    {
        $raw = $request->cookies->get(self::COOKIE_NAME);
        if (!is_string($raw) || $raw === '') {
            return ['items' => [], 'invalid' => false];
        }

        $parts = explode('.', $raw, 2);
        if (count($parts) !== 2) {
            return ['items' => [], 'invalid' => true];
        }

        [$encodedPayload, $signature] = $parts;
        if (!hash_equals($this->sign($encodedPayload), $signature)) {
            return ['items' => [], 'invalid' => true];
        }

        $json = $this->base64UrlDecode($encodedPayload);
        if ($json === null) {
            return ['items' => [], 'invalid' => true];
        }

        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            return ['items' => [], 'invalid' => true];
        }

        $exp = $payload['exp'] ?? 0;
        if (!is_int($exp) || $exp < time()) {
            return ['items' => [], 'invalid' => true];
        }

        $items = $this->normalizeItems($payload['items'] ?? []);

        return ['items' => $items, 'invalid' => false];
    }

    /**
     * @param list<array{productId: int, quantity: int}> $items
     */
    public function attach(Response $response, Request $request, array $items): void
    {
        $normalized = $this->normalizeItems($items);

        if ($normalized === []) {
            $this->clear($response, $request);
            return;
        }

        $payload = [
            'v' => self::VERSION,
            'exp' => time() + self::COOKIE_TTL,
            'items' => $normalized,
        ];

        $encodedPayload = $this->base64UrlEncode((string) json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->sign($encodedPayload);

        $response->headers->setCookie(
            Cookie::create(
                name: self::COOKIE_NAME,
                value: $encodedPayload . '.' . $signature,
                expire: time() + self::COOKIE_TTL,
                path: '/',
                secure: $this->isSecureContext($request),
                httpOnly: true,
                sameSite: Cookie::SAMESITE_LAX,
            ),
        );
    }

    public function clear(Response $response, Request $request): void
    {
        $response->headers->setCookie(
            Cookie::create(
                name: self::COOKIE_NAME,
                value: '',
                expire: 1,
                path: '/',
                secure: $this->isSecureContext($request),
                httpOnly: true,
                sameSite: Cookie::SAMESITE_LAX,
            ),
        );
    }

    /**
     * @param mixed $rawItems
     * @return list<array{productId: int, quantity: int}>
     */
    public function normalizeItems(mixed $rawItems): array
    {
        if (!is_array($rawItems)) {
            return [];
        }

        $normalized = [];

        foreach ($rawItems as $row) {
            if (!is_array($row)) {
                continue;
            }

            $productId = $row['productId'] ?? null;
            $quantity = $row['quantity'] ?? null;

            if (!is_numeric($productId) || !is_numeric($quantity)) {
                continue;
            }

            $productId = (int) $productId;
            $quantity = (int) $quantity;

            if ($productId < 1 || $quantity < 1) {
                continue;
            }

            $existing = $normalized[$productId] ?? 0;
            $normalized[$productId] = min($existing + $quantity, self::MAX_QUANTITY);

            if (count($normalized) >= self::MAX_ITEMS) {
                break;
            }
        }

        $result = [];
        foreach ($normalized as $productId => $quantity) {
            $result[] = [
                'productId' => $productId,
                'quantity' => $quantity,
            ];
        }

        return $result;
    }

    private function sign(string $encodedPayload): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, $this->secret, true));
    }

    private function isSecureContext(Request $request): bool
    {
        return $request->isSecure()
            || in_array($request->getHost(), ['localhost', '127.0.0.1'], true);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $base64 = strtr($value, '-_', '+/');
        $padding = strlen($base64) % 4;
        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            return null;
        }

        return $decoded;
    }
}
