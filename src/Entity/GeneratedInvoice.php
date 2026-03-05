<?php

declare(strict_types=1);

namespace CashierBundle\Entity;

use CashierBundle\Repository\GeneratedInvoiceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GeneratedInvoiceRepository::class)]
#[ORM\Table(name: 'cashier_generated_invoices')]
#[ORM\UniqueConstraint(name: 'cashier_generated_invoice_stripe_invoice_unique', columns: ['stripe_invoice_id'])]
#[ORM\HasLifecycleCallbacks]
class GeneratedInvoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StripeCustomer::class)]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?StripeCustomer $customer = null;

    #[ORM\Column(name: 'billable_id', nullable: true)]
    private ?int $billableId = null;

    #[ORM\Column(name: 'billable_type', length: 255, nullable: true)]
    private ?string $billableType = null;

    #[ORM\Column(name: 'stripe_invoice_id', length: 255, nullable: true)]
    private ?string $stripeInvoiceId = null;

    #[ORM\Column(name: 'stripe_payment_intent_id', length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(name: 'stripe_checkout_session_id', length: 255, nullable: true)]
    private ?string $stripeCheckoutSessionId = null;

    #[ORM\Column(name: 'resource_type', length: 100, nullable: true)]
    private ?string $resourceType = null;

    #[ORM\Column(name: 'resource_id', length: 255, nullable: true)]
    private ?string $resourceId = null;

    #[ORM\Column(name: 'plan_code', length: 100, nullable: true)]
    private ?string $planCode = null;

    #[ORM\Column(length: 10)]
    private string $currency = 'usd';

    #[ORM\Column(name: 'amount_total', type: 'integer')]
    private int $amountTotal = 0;

    #[ORM\Column(length: 50)]
    private string $status = 'paid';

    #[ORM\Column(length: 255)]
    private string $filename;

    #[ORM\Column(name: 'relative_path', length: 500)]
    private string $relativePath;

    #[ORM\Column(name: 'mime_type', length: 100)]
    private string $mimeType = 'application/pdf';

    #[ORM\Column(type: 'integer')]
    private int $size = 0;

    #[ORM\Column(length: 64)]
    private string $checksum;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $payload = null;

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

    public function getCustomer(): ?StripeCustomer
    {
        return $this->customer;
    }

    public function setCustomer(?StripeCustomer $customer): self
    {
        $this->customer = $customer;
        $this->billableId = $customer?->getBillableId();
        $this->billableType = $customer?->getBillableType();

        return $this;
    }

    public function getBillableId(): ?int
    {
        return $this->billableId;
    }

    public function getBillableType(): ?string
    {
        return $this->billableType;
    }

    public function getStripeInvoiceId(): ?string
    {
        return $this->stripeInvoiceId;
    }

    public function setStripeInvoiceId(?string $stripeInvoiceId): self
    {
        $this->stripeInvoiceId = $stripeInvoiceId;

        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): self
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }

    public function getStripeCheckoutSessionId(): ?string
    {
        return $this->stripeCheckoutSessionId;
    }

    public function setStripeCheckoutSessionId(?string $stripeCheckoutSessionId): self
    {
        $this->stripeCheckoutSessionId = $stripeCheckoutSessionId;

        return $this;
    }

    public function getResourceType(): ?string
    {
        return $this->resourceType;
    }

    public function setResourceType(?string $resourceType): self
    {
        $this->resourceType = $resourceType;

        return $this;
    }

    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    public function setResourceId(?string $resourceId): self
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function getPlanCode(): ?string
    {
        return $this->planCode;
    }

    public function setPlanCode(?string $planCode): self
    {
        $this->planCode = $planCode;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getAmountTotal(): int
    {
        return $this->amountTotal;
    }

    public function setAmountTotal(int $amountTotal): self
    {
        $this->amountTotal = $amountTotal;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    public function setRelativePath(string $relativePath): self
    {
        $this->relativePath = $relativePath;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function setChecksum(string $checksum): self
    {
        $this->checksum = $checksum;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    public function setPayload(?array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
