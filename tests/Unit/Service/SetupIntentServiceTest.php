<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service;

use CashierBundle\Service\SetupIntentService;
use CashierBundle\Tests\Support\TestStripeClient;
use PHPUnit\Framework\TestCase;
use Stripe\Exception\InvalidRequestException;
use Stripe\SetupIntent;

final class SetupIntentServiceTest extends TestCase
{
    public function testFindReturnsNullOnStripeError(): void
    {
        $stripe = (new TestStripeClient())->withService('setupIntents', new class () {
            public function retrieve(string $id): SetupIntent
            {
                throw new InvalidRequestException('not found');
            }
        });

        $service = new SetupIntentService($stripe);

        self::assertNull($service->find('seti_missing'));
    }

    public function testCreateReturnsSetupIntentModel(): void
    {
        $intent = new SetupIntent('seti_123');
        $intent->client_secret = 'seti_secret';
        $intent->status = 'requires_confirmation';

        $stripe = (new TestStripeClient())->withService('setupIntents', new class ($intent) {
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
        });

        $service = new SetupIntentService($stripe);
        $setupIntent = $service->create();

        self::assertSame('seti_123', $setupIntent->id());
        self::assertSame('seti_secret', $setupIntent->clientSecret());
    }
}
