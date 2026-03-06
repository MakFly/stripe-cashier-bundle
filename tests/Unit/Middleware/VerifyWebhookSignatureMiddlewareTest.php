<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Middleware;

use CashierBundle\Middleware\VerifyWebhookSignatureMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Test suite for VerifyWebhookSignatureMiddleware. */
final class VerifyWebhookSignatureMiddlewareTest extends TestCase
{
    private string $secret = 'whsec_test_secret';

    public function testVerifyAcceptsValidSignature(): void
    {
        $payload = json_encode([
            'id' => 'evt_123',
            'object' => 'event',
            'type' => 'invoice.paid',
            'data' => ['object' => ['object' => 'invoice', 'id' => 'in_123']],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->makeSignature($payload);
        $request = new Request([], [], [], [], [], [], $payload);
        $request->headers->set('Stripe-Signature', $signature);

        $middleware = new VerifyWebhookSignatureMiddleware($this->secret, 300);

        $middleware->verify($request);

        self::assertTrue(true);
    }

    public function testVerifyThrowsHttpExceptionOnInvalidSignature(): void
    {
        $payload = '{"id":"evt_123"}';
        $request = new Request([], [], [], [], [], [], $payload);
        $request->headers->set('Stripe-Signature', 't=1,v1=invalid');

        $middleware = new VerifyWebhookSignatureMiddleware($this->secret, 300);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid signature');

        $middleware->verify($request);
    }

    private function makeSignature(string $payload): string
    {
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $hash = hash_hmac('sha256', $signedPayload, $this->secret);

        return "t={$timestamp},v1={$hash}";
    }
}
