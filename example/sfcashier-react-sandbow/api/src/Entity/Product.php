<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Repository\ProductRepository;
use App\State\ProductProvider;
use Cocur\Slugify\Slugify;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['product:read']],
    order: ['name' => 'ASC'],
    operations: [
        new GetCollection(),
        new Get(
            uriTemplate: '/products/{slug}',
            uriVariables: [
                'slug' => new Link(fromClass: Product::class, identifiers: ['slug']),
            ],
            provider: ProductProvider::class,
        ),
    ],
)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read', 'cart:item:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'cart:item:read'])]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['product:read', 'cart:item:read'])]
    private string $slug;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:read', 'cart:item:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['product:read', 'cart:item:read'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $price; // Stored in centimes

    #[ORM\Column(length: 512, nullable: true)]
    #[Groups(['product:read', 'cart:item:read'])]
    private ?string $imageUrl = null;

    #[ORM\Column]
    #[Groups(['product:read', 'cart:item:read'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $stock = 0;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        if ($this->slug === '' || $this->slug === '0') {
            $this->generateSlug();
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        if ($this->slug === '' || $this->slug === '0') {
            $this->generateSlug();
        }
    }

    private function generateSlug(): void
    {
        $slugify = new Slugify();
        $this->slug = $slugify->slugify($this->name);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getFormattedPrice(): string
    {
        return number_format($this->price / 100, 2, '.', ' ');
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
