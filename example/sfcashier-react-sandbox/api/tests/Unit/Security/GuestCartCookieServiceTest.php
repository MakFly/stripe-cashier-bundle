<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\GuestCartCookieService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class GuestCartCookieServiceTest extends TestCase
{
    public function testNormalizeItemsDeduplicatesAndCapsQuantity(): void
    {
        $service = new GuestCartCookieService('unit_test_secret_32_chars_minimum_2026');

        $normalized = $service->normalizeItems([
            ['productId' => 10, 'quantity' => 2],
            ['productId' => 10, 'quantity' => 200],
            ['productId' => 20, 'quantity' => 1],
            ['productId' => 0, 'quantity' => 3],
            ['productId' => 30, 'quantity' => 0],
            ['productId' => 'foo', 'quantity' => 2],
            'invalid',
        ]);

        self::assertSame([
            ['productId' => 10, 'quantity' => 99],
            ['productId' => 20, 'quantity' => 1],
        ], $normalized);
    }

    public function testDecodeFromRequestMarksInvalidCookie(): void
    {
        $service = new GuestCartCookieService('unit_test_secret_32_chars_minimum_2026');

        $request = Request::create(
            uri: 'http://localhost/api/v1/cart/guest',
            method: 'GET',
            cookies: ['sfcashier_guest_cart' => 'invalid.cookie-format'],
        );

        $decoded = $service->decodeFromRequest($request);

        self::assertTrue($decoded['invalid']);
        self::assertSame([], $decoded['items']);
    }
}
