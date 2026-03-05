<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Model\SetupIntent;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class SetupIntentService
{
    public function __construct(
        private readonly StripeClient $stripe,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function create(array $options = []): SetupIntent
    {
        $payload = array_merge([
            'payment_method_types' => ['card'],
        ], $options);

        try {
            $setupIntent = $this->stripe->setupIntents->create($payload);

            return new SetupIntent($setupIntent);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to create setup intent: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    public function find(string $id): ?SetupIntent
    {
        try {
            $setupIntent = $this->stripe->setupIntents->retrieve($id);

            return new SetupIntent($setupIntent);
        } catch (ApiErrorException $e) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function update(string $id, array $options = []): SetupIntent
    {
        try {
            $setupIntent = $this->stripe->setupIntents->update($id, $options);

            return new SetupIntent($setupIntent);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to update setup intent %s: %s', $id, $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function confirm(string $id, array $options = []): SetupIntent
    {
        try {
            $setupIntent = $this->stripe->setupIntents->confirm($id, $options);

            return new SetupIntent($setupIntent);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to confirm setup intent %s: %s', $id, $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function cancel(string $id, array $options = []): SetupIntent
    {
        try {
            $setupIntent = $this->stripe->setupIntents->cancel($id, $options);

            return new SetupIntent($setupIntent);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to cancel setup intent %s: %s', $id, $e->getMessage()),
                0,
                $e,
            );
        }
    }
}
