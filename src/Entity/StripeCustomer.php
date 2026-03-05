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
#[ORM\UniqueConstraint(name: 'billable_lookup_unique', columns: ['billable_type', 'billable_id'])]
#[ORM\HasLifecycleCallbacks]
class StripeCustomer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'billable_id', nullable: true)]
    private ?int $billableId = null;

    #[ORM\Column(name: 'billable_type', length: 255, nullable: true)]
    private ?string $billableType = null;

    private ?BillableEntityInterface $billable = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $stripeId;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $currency = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $balance = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $address = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $pmType = null;

    #[ORM\Column(length: 4, nullable: true)]
    private ?string $pmLastFour = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $invoicePrefix = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $taxExempt = null;

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

    public function getBillableId(): ?int
    {
        return $this->billableId;
    }

    public function setBillableId(?int $billableId): self
    {
        $this->billableId = $billableId;

        return $this;
    }

    public function getBillableType(): ?string
    {
        return $this->billableType;
    }

    public function setBillableType(?string $billableType): self
    {
        $this->billableType = $billableType;

        return $this;
    }

    public function getBillable(): ?BillableEntityInterface
    {
        return $this->billable;
    }

    public function setBillable(BillableEntityInterface $billable): self
    {
        $this->billable = $billable;
        $this->billableId = $billable->getId();
        $this->billableType = $billable::class;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getBalance(): ?int
    {
        return $this->balance;
    }

    public function setBalance(?int $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAddress(): ?array
    {
        return $this->address;
    }

    /**
     * @param array<string, mixed>|null $address
     */
    public function setAddress(?array $address): self
    {
        $this->address = $address;

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

    public function getInvoicePrefix(): ?string
    {
        return $this->invoicePrefix;
    }

    public function setInvoicePrefix(?string $invoicePrefix): self
    {
        $this->invoicePrefix = $invoicePrefix;

        return $this;
    }

    public function getTaxExempt(): ?string
    {
        return $this->taxExempt;
    }

    public function setTaxExempt(?string $taxExempt): self
    {
        $this->taxExempt = $taxExempt;

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
