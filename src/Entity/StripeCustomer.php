<?php

declare(strict_types=1);

namespace CashierBundle\Entity;

use CashierBundle\Contract\BillableEntityInterface;
use CashierBundle\Repository\StripeCustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StripeCustomerRepository::class)]
#[ORM\Table(name: 'cashier_customers')]
#[ORM\UniqueConstraint(name: 'stripe_id_unique', columns: ['stripe_id'])]
#[ORM\HasLifecycleCallbacks]
class StripeCustomer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'stripeCustomer')]
    #[ORM\JoinColumn(name: 'billable_id', referencedColumnName: 'id', nullable: false)]
    private BillableEntityInterface $billable;

    #[ORM\Column(length: 255, unique: true)]
    private string $stripeId;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $pmType = null;

    #[ORM\Column(length: 4, nullable: true)]
    private ?string $pmLastFour = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $trialEndsAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Subscription::class)]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->subscriptions = new ArrayCollection();
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

    public function getBillable(): BillableEntityInterface
    {
        return $this->billable;
    }

    public function setBillable(BillableEntityInterface $billable): self
    {
        $this->billable = $billable;
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

    public function getPmType(): ?string
    {
        return $this->pmType;
    }

    public function setPmType(?string $pmType): self
    {
        $this->pmType = $pmType;
        return $this;
    }

    public function getPmLastFour(): ?string
    {
        return $this->pmLastFour;
    }

    public function setPmLastFour(?string $pmLastFour): self
    {
        $this->pmLastFour = $pmLastFour;
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
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): self
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setCustomer($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): self
    {
        if ($this->subscriptions->removeElement($subscription)) {
            if ($subscription->getCustomer() === $this) {
                $subscription->setCustomer(null);
            }
        }

        return $this;
    }

    public function onTrial(): bool
    {
        return $this->trialEndsAt !== null && $this->trialEndsAt > new \DateTimeImmutable();
    }

    public function onGenericTrial(): bool
    {
        return $this->onTrial();
    }
}
