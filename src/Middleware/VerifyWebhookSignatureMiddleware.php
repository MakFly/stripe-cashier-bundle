<?php

declare(strict_types=1);

namespace CashierBundle\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

final readonly class VerifyWebhookSignatureMiddleware
{
    public function __construct(
        private string $webhookSecret,
        private int $tolerance = 300,
    ) {
    }

    public function verify(Request $request): void
    {
        $signature = $request->headers->get('Stripe-Signature');
        $payload = $request->getContent();

        try {
            \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $this->webhookSecret,
                $this->tolerance,
            );
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Invalid signature');
        }
    }
}
