<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service;

use CashierBundle\Service\SetupIntentService;
use PHPUnit\Framework\TestCase;
use Stripe\Exception\InvalidRequestException;
use Stripe\SetupIntent;
use Stripe\StripeClient;

final class SetupIntentServiceTest extends TestCase
{
    public function testFindReturnsNullOnStripeError(): void
    {
        $stripe = new StripeClient('sk_test');
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->setupIntents = new class () {
            public function retrieve(string $id): SetupIntent
            {
                throw new InvalidRequestException('not found');
            }
        };

        $service = new SetupIntentService($stripe);

        self::assertNull($service->find('seti_missing'));
    }

    public function testCreateReturnsSetupIntentModel(): void
    {
        $intent = new SetupIntent('seti_123');
        $intent->client_secret = 'seti_secret';
        $intent->status = 'requires_confirmation';

        $stripe = new StripeClient('sk_test');
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->setupIntents = new class ($intent) {
            public function __construct(private SetupIntent $intent)
            {
            }

            /**
             * @param array<string, mixed> $options
             */
            public function create(array $options): SetupIntent
            {
                return $this->intent;
            }
        };

        $service = new SetupIntentService($stripe);
        $setupIntent = $service->create();

        self::assertSame('seti_123', $setupIntent->id());
        self::assertSame('seti_secret', $setupIntent->clientSecret());
    }
}
