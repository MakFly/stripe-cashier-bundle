<?php

declare(strict_types=1);

namespace CashierBundle\Entity;

use CashierBundle\Repository\SubscriptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\Table(name: 'cashier_subscriptions')]
#[ORM\Index(columns: ['customer_id', 'stripe_status'])]
#[ORM\HasLifecycleCallbacks]
/**
 * Represents a Stripe subscription linked to a customer, tracking its lifecycle and status.
 */
class Subscription
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_INCOMPLETE = 'incomplete';
    public const STATUS_INCOMPLETE_EXPIRED = 'incomplete_expired';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_TRIALING = 'trialing';
    public const STATUS_UNPAID = 'unpaid';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(name: 'customer_id', nullable: false)]
    private StripeCustomer $customer;

    #[ORM\Column(length: 255)]
    private string $type = 'default';

    #[ORM\Column(name: 'stripe_id', length: 255, unique: true)]
    private string $stripeId;

    #[ORM\Column(name: 'stripe_status', length: 50)]
    private string $stripeStatus;

    #[ORM\Column(name: 'stripe_price', length: 255, nullable: true)]
    private ?string $stripePrice = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $quantity = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $trialEndsAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endsAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'subscription', targetEntity: SubscriptionItem::class, cascade: ['persist', 'remove'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): StripeCustomer
    {
        return $this->customer;
    }

    public function setCustomer(StripeCustomer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStripeId(): string
    {
        return $this->stripeId;
    }

    public function setStripeId(string $stripeId): self
    {
        $this->stripeId = $stripeId;
        return $this;
    }

    public function getStripeStatus(): string
    {
        return $this->stripeStatus;
    }

    public function setStripeStatus(string $stripeStatus): self
    {
        $this->stripeStatus = $stripeStatus;
        return $this;
    }

    public function getStripePrice(): ?string
    {
        return $this->stripePrice;
    }

    public function setStripePrice(?string $stripePrice): self
    {
        $this->stripePrice = $stripePrice;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getTrialEndsAt(): ?\DateTimeImmutable
    {
        return $this->trialEndsAt;
    }

    public function setTrialEndsAt(?\DateTimeImmutable $trialEndsAt): self
    {
        $this->trialEndsAt = $trialEndsAt;
        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeImmutable $endsAt): self
    {
        $this->endsAt = $endsAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, SubscriptionItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(SubscriptionItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setSubscription($this);
        }

        return $this;
    }

    public function removeItem(SubscriptionItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getSubscription() === $this) {
                $item->setSubscription(null);
            }
        }

        return $this;
    }

    // Status methods

    /**
     * Returns true if the subscription is active, on trial, or on grace period.
     */
    public function valid(): bool
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }

    /**
     * Returns true if the subscription is active and not paused.
     */
    public function active(): bool
    {
        return $this->stripeStatus === self::STATUS_ACTIVE || $this->onTrial();
    }

    /**
     * Returns true if the subscription is within its trial period.
     */
    public function onTrial(): bool
    {
        return $this->trialEndsAt !== null && $this->trialEndsAt > new \DateTimeImmutable();
    }

    /**
     * Returns true if the subscription is canceled but still within the grace period.
     */
    public function onGracePeriod(): bool
    {
        return $this->endsAt !== null && $this->endsAt > new \DateTimeImmutable();
    }

    /**
     * Returns true if the subscription has been canceled.
     */
    public function canceled(): bool
    {
        return $this->endsAt !== null;
    }

    /**
     * Returns true if the subscription has ended.
     */
    public function ended(): bool
    {
        return $this->canceled() && $this->endsAt->getTimestamp() <= (new \DateTimeImmutable())->getTimestamp();
    }

    public function incomplete(): bool
    {
        return $this->stripeStatus === self::STATUS_INCOMPLETE;
    }

    public function pastDue(): bool
    {
        return $this->stripeStatus === self::STATUS_PAST_DUE;
    }

    /**
     * Returns true if the subscription is incomplete and has expired.
     */
    public function incompleteAndExpired(): bool
    {
        return $this->stripeStatus === self::STATUS_INCOMPLETE_EXPIRED;
    }

    public function notOnGracePeriod(): bool
    {
        return !$this->onGracePeriod();
    }

    public function notOnTrial(): bool
    {
        return !$this->onTrial();
    }

    /**
     * Returns true if the subscription is active, not on trial, and not on grace period.
     */
    public function recurring(): bool
    {
        return $this->active() && !$this->onTrial() && !$this->onGracePeriod();
    }

    /**
     * Returns true if the subscription is paused.
     */
    public function paused(): bool
    {
        return $this->stripeStatus === self::STATUS_PAUSED;
    }

    public function onPausedGracePeriod(): bool
    {
        return $this->paused() && $this->endsAt !== null && $this->endsAt > new \DateTimeImmutable();
    }

    public function notPaused(): bool
    {
        return !$this->paused();
    }

    public function notPausedOrOnPausedGracePeriod(): bool
    {
        return !$this->paused() && !$this->onPausedGracePeriod();
    }
}
