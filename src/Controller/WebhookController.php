<?php

declare(strict_types=1);

namespace CashierBundle\Controller;

use CashierBundle\Webhook\WebhookProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class WebhookController
{
    public function __construct(
        private readonly WebhookProcessor $processor,
    ) {
    }

    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature');

        try {
            $this->processor->process($payload, $signature);

            return new Response('Webhook handled', Response::HTTP_OK);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
