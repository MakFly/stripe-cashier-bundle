<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Controller;

use CashierBundle\Controller\WebhookController;
use CashierBundle\Webhook\WebhookProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class WebhookControllerTest extends TestCase
{
    private string $secret = 'whsec_controller_test_secret';

    public function testHandleReturnsOkOnSuccessfulProcessing(): void
    {
        $payload = json_encode([
            'id' => 'evt_controller_1',
            'object' => 'event',
            'type' => 'invoice.paid',
            'data' => ['object' => ['object' => 'invoice', 'id' => 'in_1']],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->makeSignature($payload);
        $request = new Request([], [], [], [], [], [], $payload);
        $request->headers->set('Stripe-Signature', $signature);

        $controller = new WebhookController($this->createProcessor());

        $response = $controller->handle($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('Webhook handled', $response->getContent());
    }

    public function testHandleReturnsBadRequestWhenProcessorThrows(): void
    {
        $request = new Request([], [], [], [], [], [], '{}');

        $controller = new WebhookController($this->createProcessor());

        $response = $controller->handle($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertStringContainsString('Stripe signature header is required.', (string) $response->getContent());
    }

    private function createProcessor(): WebhookProcessor
    {
        $locator = new class () implements ContainerInterface {
            public function get(string $id)
            {
                throw new \RuntimeException('No services');
            }

            public function has(string $id): bool
            {
                return false;
            }

            /**
             * @return list<string>
             */
            public function getServiceIds(): array
            {
                return [];
            }

            /**
             * @return array<string, string>
             */
            public function getProvidedServices(): array
            {
                return [];
            }
        };

        return new WebhookProcessor($locator, new EventDispatcher(), $this->secret, 300);
    }

    private function makeSignature(string $payload): string
    {
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $hash = hash_hmac('sha256', $signedPayload, $this->secret);

        return "t={$timestamp},v1={$hash}";
    }
}
