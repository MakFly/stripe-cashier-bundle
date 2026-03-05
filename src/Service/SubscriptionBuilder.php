<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Contract\BillableInterface;
use CashierBundle\Entity\StripeCustomer;
use CashierBundle\Entity\Subscription;
use CashierBundle\Entity\SubscriptionItem;
use CashierBundle\Exception\CustomerAlreadyCreatedException;
use CashierBundle\Exception\InvalidCouponException;
use CashierBundle\Model\PaymentMethod;
use CashierBundle\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Subscription as StripeSubscription;

/**
 * @phpstan-type SubscriptionItem array{price: string, quantity: int|null, metadata: array<string, mixed>}
 */
class SubscriptionBuilder
{
    /** @var array<SubscriptionItem> */
    private array $items = [];

    /** @var array<string, mixed> */
    private array $options = [];

    private ?int $trialDays = null;
    private ?\DateTimeInterface $trialEnd = null;
    private bool $skipTrial = false;
    private ?string $coupon = null;
    private ?string $promotionCode = null;
    /** @var array<string, mixed> */
    private array $metadata = [];
    private string $paymentBehavior = 'default_incomplete';
    private bool $prorate = true;
    private ?\DateTimeInterface $billingCycleAnchor = null;

    public function __construct(
        private readonly BillableInterface $billable,
        private readonly string $type,
        private readonly StripeClient $stripe,
        private readonly EntityManagerInterface $entityManager,
        private readonly SubscriptionRepository $subscriptionRepository
    ) {
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function price(string $price, int $quantity = 1, array $metadata = []): self
    {
        $this->items[] = [
            'price' => $price,
            'quantity' => $quantity,
            'metadata' => $metadata,
        ];

        return $this;
    }

    public function meteredPrice(string $price): self
    {
        $this->items[] = [
            'price' => $price,
            'quantity' => null,
            'metadata' => [],
        ];

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function withMetadata(array $options): self
    {
        $this->metadata = $options;

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function withOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function quantity(int $quantity): self
    {
        if (empty($this->items)) {
            throw new \RuntimeException('No price has been added to the subscription.');
        }

        $this->items[array_key_last($this->items)]['quantity'] = $quantity;

        return $this;
    }

    public function trialDays(int $days): self
    {
        $this->trialDays = $days;
        $this->trialEnd = null;
        $this->skipTrial = false;

        return $this;
    }

    public function trialUntil(\DateTimeInterface $date): self
    {
        $this->trialEnd = $date;
        $this->trialDays = null;
        $this->skipTrial = false;

        return $this;
    }

    public function skipTrial(): self
    {
        $this->skipTrial = true;
        $this->trialDays = null;
        $this->trialEnd = null;

        return $this;
    }

    public function anchorBillingCycleOn(\DateTimeInterface $date): self
    {
        $this->billingCycleAnchor = $date;

        return $this;
    }

    /**
     * @param array<string, mixed> $thresholds
     */
    public function withBillingThresholds(array $thresholds): self
    {
        $this->options['billing_thresholds'] = $thresholds;

        return $this;
    }

    public function withCoupon(?string $couponId): self
    {
        $this->coupon = $couponId;
        $this->promotionCode = null;

        return $this;
    }

    public function withPromotionCode(?string $code): self
    {
        $this->promotionCode = $code;
        $this->coupon = null;

        return $this;
    }

    public function withPaymentBehavior(string $behavior): self
    {
        $this->paymentBehavior = $behavior;

        return $this;
    }

    public function noProrate(): self
    {
        $this->prorate = false;

        return $this;
    }

    public function prorate(): self
    {
        $this->prorate = true;

        return $this;
    }

    /**
     * @param PaymentMethod|string|null $paymentMethod
     */
    public function create(PaymentMethod|string|null $paymentMethod = null): Subscription
    {
        $customer = $this->getStripeCustomer();

        if ($customer === null) {
            throw new CustomerAlreadyCreatedException('Stripe customer has not been created yet.');
        }

        $stripeSubscription = $this->createStripeSubscription($customer, $paymentMethod);

        return $this->createLocalSubscription($stripeSubscription);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(string $customerId, PaymentMethod|string|null $paymentMethod): array
    {
        $payload = array_merge([
            'customer' => $customerId,
            'items' => array_map(fn ($item) => [
                'price' => $item['price'],
                'quantity' => $item['quantity'],
            ], $this->items),
            'payment_behavior' => $this->paymentBehavior,
            'payment_settings' => [
                'payment_method_types' => ['card'],
                'save_default_payment_method' => 'on_subscription',
            ],
            'expand' => ['latest_invoice.payment_intent'],
        ], $this->options);

        if ($paymentMethod !== null) {
            $payload['default_payment_method'] = $paymentMethod instanceof PaymentMethod
                ? $paymentMethod->id()
                : $paymentMethod;
        }

        if ($this->skipTrial) {
            $payload['trial_period_days'] = 0;
        } elseif ($this->trialDays !== null) {
            $payload['trial_period_days'] = $this->trialDays;
        } elseif ($this->trialEnd !== null) {
            $payload['trial_end'] = $this->trialEnd->getTimestamp();
        }

        if ($this->coupon !== null) {
            $payload['coupon'] = $this->coupon;
        } elseif ($this->promotionCode !== null) {
            $payload['promotion_code'] = $this->promotionCode;
        }

        if (!empty($this->metadata)) {
            $payload['metadata'] = $this->metadata;
        }

        if ($this->billingCycleAnchor !== null) {
            $payload['billing_cycle_anchor'] = $this->billingCycleAnchor->getTimestamp();
        }

        if (!$this->prorate) {
            $payload['proration_behavior'] = 'none';
        }

        return $payload;
    }

    /**
     * @param PaymentMethod|string|null $paymentMethod
     */
    private function createStripeSubscription(string $customerId, PaymentMethod|string|null $paymentMethod): StripeSubscription
    {
        try {
            return $this->stripe->subscriptions->create(
                $this->buildPayload($customerId, $paymentMethod)
            );
        } catch (\Stripe\Exception\InvalidCouponException $e) {
            throw new InvalidCouponException('The provided coupon code is invalid.', 0, $e);
        }
    }

    private function createLocalSubscription(StripeSubscription $stripeSubscription): Subscription
    {
        $customer = $this->getLocalCustomer();

        if ($customer === null) {
            throw new \RuntimeException('Local customer not found.');
        }

        $subscription = new Subscription();
        $subscription->setCustomer($customer);
        $subscription->setType($this->type);
        $subscription->setStripeId($stripeSubscription->id);
        $subscription->setStripeStatus($stripeSubscription->status);

        if (isset($stripeSubscription->items->data[0])) {
            $subscription->setStripePrice($stripeSubscription->items->data[0]->price->id);
            $subscription->setQuantity($stripeSubscription->items->data[0]->quantity ?? 1);
        }

        if ($stripeSubscription->trial_end !== null) {
            $subscription->setTrialEndsAt(
                \DateTimeImmutable::createFromFormat('U', (string) $stripeSubscription->trial_end) ?: null
            );
        }

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        foreach ($stripeSubscription->items->data as $stripeItem) {
            $item = new SubscriptionItem();
            $item->setSubscription($subscription);
            $item->setStripeId($stripeItem->id);
            $item->setStripeProduct($stripeItem->price->product);
            $item->setStripePrice($stripeItem->price->id);
            $item->setQuantity($stripeItem->quantity ?? 1);

            $this->entityManager->persist($item);
        }

        $this->entityManager->flush();

        return $subscription;
    }

    private function getStripeCustomer(): ?string
    {
        return $this->billable->stripeId();
    }

    private function getLocalCustomer(): ?StripeCustomer
    {
        $stripeId = $this->billable->stripeId();
        if ($stripeId === null) {
            return null;
        }

        /** @var StripeCustomerRepository $repository */
        $repository = $this->entityManager->getRepository(StripeCustomer::class);

        return $repository->findByStripeId($stripeId);
    }
}
