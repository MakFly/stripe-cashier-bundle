<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Billing\SubscriptionCatalog;
use CashierBundle\Entity\Subscription;

class SubscriptionSerializer
{
    public function __construct(
        private readonly SubscriptionCatalog $catalog,
    ) {}

    public function serialize(?Subscription $subscription): array
    {
        if (!$subscription instanceof Subscription) {
            return [
                'hasSubscription' => false,
                'subscription' => null,
            ];
        }

        $matchedPlan = $this->catalog->matchStripePrice($subscription->getStripePrice());

        return [
            'hasSubscription' => true,
            'subscription' => [
                'id' => $subscription->getId(),
                'type' => $subscription->getType(),
                'stripeId' => $subscription->getStripeId(),
                'stripeStatus' => $subscription->getStripeStatus(),
                'stripePrice' => $subscription->getStripePrice(),
                'quantity' => $subscription->getQuantity(),
                'trialEndsAt' => $subscription->getTrialEndsAt()?->format(\DateTimeInterface::ATOM),
                'endsAt' => $subscription->getEndsAt()?->format(\DateTimeInterface::ATOM),
                'createdAt' => $subscription->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updatedAt' => $subscription->getUpdatedAt()->format(\DateTimeInterface::ATOM),
                'isActive' => $subscription->active(),
                'isOnTrial' => $subscription->onTrial(),
                'isOnGracePeriod' => $subscription->onGracePeriod(),
                'canCancel' => $subscription->valid() && !$subscription->canceled(),
                'canResume' => $subscription->onGracePeriod(),
                'canManageBilling' => true,
                'plan' => $matchedPlan === null ? null : [
                    'code' => $matchedPlan['code'],
                    'name' => $matchedPlan['name'],
                    'billingCycle' => $matchedPlan['billingCycle'],
                    'trialDays' => $matchedPlan['trialDays'],
                ],
            ],
        ];
    }
}
