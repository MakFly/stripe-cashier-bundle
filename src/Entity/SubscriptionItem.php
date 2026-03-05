<?php

declare(strict_types=1);

namespace CashierBundle\Entity;

use CashierBundle\Repository\SubscriptionItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionItemRepository::class)]
#[ORM\Table(name: 'cashier_subscription_items')]
#[ORM\Index(columns: ['subscription_id', 'stripe_price'])]
#[ORM\HasLifecycleCallbacks]
class SubscriptionItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'subscription_id', nullable: false)]
    private Subscription $subscription;

    #[ORM\Column(name: 'stripe_id', length: 255, unique: true)]
    private string $stripeId;

    #[ORM\Column(name: 'stripe_product', length: 255)]
    private string $stripeProduct;

    #[ORM\Column(name: 'stripe_price', length: 255)]
    private string $stripePrice;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $quantity = null;

    #[ORM\Column(name: 'meter_id', length: 255, nullable: true)]
    private ?string $meterId = null;

    #[ORM\Column(name: 'meter_event_name', length: 255, nullable: true)]
    private ?string $meterEventName = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
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

    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }

    public function setSubscription(Subscription $subscription): self
    {
        $this->subscription = $subscription;
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

    public function getStripeProduct(): string
    {
        return $this->stripeProduct;
    }

    public function setStripeProduct(string $stripeProduct): self
    {
        $this->stripeProduct = $stripeProduct;
        return $this;
    }

    public function getStripePrice(): string
    {
        return $this->stripePrice;
    }

    public function setStripePrice(string $stripePrice): self
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

    public function getMeterId(): ?string
    {
        return $this->meterId;
    }

    public function setMeterId(?string $meterId): self
    {
        $this->meterId = $meterId;
        return $this;
    }

    public function getMeterEventName(): ?string
    {
        return $this->meterEventName;
    }

    public function setMeterEventName(?string $meterEventName): self
    {
        $this->meterEventName = $meterEventName;
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
}
