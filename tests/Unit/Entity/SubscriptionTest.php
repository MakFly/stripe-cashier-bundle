<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Entity;

use CashierBundle\Entity\Subscription;
use PHPUnit\Framework\TestCase;

final class SubscriptionTest extends TestCase
{
    private function createSubscription(array $data = []): Subscription
    {
        $subscription = new Subscription();

        $subscription->setType($data['type'] ?? 'default');
        $subscription->setStripeId($data['stripeId'] ?? 'sub_test_123');

        // Use reflection to set stripeStatus since setter doesn't exist
        $reflection = new \ReflectionClass($subscription);
        $property = $reflection->getProperty('stripeStatus');
        $property->setAccessible(true);
        $property->setValue($subscription, $data['stripeStatus'] ?? 'active');

        if (isset($data['trialEndsAt'])) {
            $subscription->setTrialEndsAt($data['trialEndsAt']);
        }
        if (isset($data['endsAt'])) {
            $subscription->setEndsAt($data['endsAt']);
        }

        return $subscription;
    }

    public function testActiveReturnsTrueWhenStatusIsActive(): void
    {
        $subscription = $this->createSubscription(['stripeStatus' => 'active']);

        $this->assertTrue($subscription->active());
    }

    public function testActiveReturnsTrueWhenOnTrial(): void
    {
        $subscription = $this->createSubscription([
            'stripeStatus' => 'active',
            'trialEndsAt' => new \DateTimeImmutable('+5 days'),
        ]);

        $this->assertTrue($subscription->active());
    }

    public function testOnTrialReturnsTrueWhenTrialEndsAtIsInFuture(): void
    {
        $subscription = $this->createSubscription([
            'stripeStatus' => 'active',
            'trialEndsAt' => new \DateTimeImmutable('+5 days'),
        ]);

        $this->assertTrue($subscription->onTrial());
    }

    public function testOnTrialReturnsFalseWhenNoTrialEndsAt(): void
    {
        $subscription = $this->createSubscription(['stripeStatus' => 'active']);

        $this->assertFalse($subscription->onTrial());
    }

    public function testCanceledReturnsTrueWhenEndsAtIsSet(): void
    {
        $subscription = $this->createSubscription([
            'stripeStatus' => 'active',
            'endsAt' => new \DateTimeImmutable('+5 days'),
        ]);

        $this->assertTrue($subscription->canceled());
    }

    public function testOnGracePeriodReturnsTrueWhenEndsAtIsInFuture(): void
    {
        $subscription = $this->createSubscription([
            'stripeStatus' => 'active',
            'endsAt' => new \DateTimeImmutable('+5 days'),
        ]);

        $this->assertTrue($subscription->onGracePeriod());
    }

    public function testEndedReturnsTrueWhenEndsAtIsInPast(): void
    {
        $subscription = $this->createSubscription([
            'stripeStatus' => 'canceled',
            'endsAt' => new \DateTimeImmutable('-1 day'),
        ]);

        $this->assertTrue($subscription->ended());
    }

    public function testIncompleteReturnsTrueWhenStatusIsIncomplete(): void
    {
        $subscription = $this->createSubscription(['stripeStatus' => 'incomplete']);

        $this->assertTrue($subscription->incomplete());
    }

    public function testPastDueReturnsTrueWhenStatusIsPastDue(): void
    {
        $subscription = $this->createSubscription(['stripeStatus' => 'past_due']);

        $this->assertTrue($subscription->pastDue());
    }

    public function testValidReturnsTrueWhenActive(): void
    {
        $subscription = $this->createSubscription(['stripeStatus' => 'active']);

        $this->assertTrue($subscription->valid());
    }

    public function testValidReturnsTrueWhenOnTrial(): void
    {
        $subscription = $this->createSubscription([
            'stripeStatus' => 'active',
            'trialEndsAt' => new \DateTimeImmutable('+5 days'),
        ]);

        $this->assertTrue($subscription->valid());
    }

    public function testValidReturnsTrueWhenPastDueWithGracePeriod(): void
    {
        $subscription = $this->createSubscription([
            'stripeStatus' => 'past_due',
            'endsAt' => new \DateTimeImmutable('+3 days'),
        ]);

        $this->assertTrue($subscription->valid());
    }

    public function testPausedReturnsTrueWhenStatusIsPaused(): void
    {
        $subscription = $this->createSubscription(['stripeStatus' => 'paused']);

        $this->assertTrue($subscription->paused());
    }

    public function testRecurringReturnsTrueWhenActiveAndNotOnTrial(): void
    {
        $subscription = $this->createSubscription([
            'stripeStatus' => 'active',
            'trialEndsAt' => null,
        ]);

        $this->assertTrue($subscription->recurring());
    }

    public function testRecurringReturnsFalseWhenOnTrial(): void
    {
        $subscription = $this->createSubscription([
            'stripeStatus' => 'active',
            'trialEndsAt' => new \DateTimeImmutable('+5 days'),
        ]);

        $this->assertFalse($subscription->recurring());
    }
}
