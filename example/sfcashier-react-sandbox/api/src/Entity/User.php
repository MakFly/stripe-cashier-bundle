<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\UserRepository;
use BetterAuth\Symfony\Model\User as BetterAuthUser;
use CashierBundle\Concerns\BillableTrait;
use CashierBundle\Contract\BillableEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: '`user`')]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['user:read']]),
        new GetCollection(normalizationContext: ['groups' => ['user:read']]),
    ],
)]
class User extends BetterAuthUser
implements BillableEntityInterface
{
    use BillableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'order:read', 'cart:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::BINARY, length: 16, unique: true)]
    #[Groups(['user:read', 'order:read', 'cart:read'])]
    private string $uuid;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'order:read', 'cart:read'])]
    private string $name = '';

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['user:read'])]
    private bool $twoFactorEnabled = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $twoFactorSecret = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $magicLinkToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $magicLinkExpiresAt = null;

    /**
     * @var Collection<int, Cart>
     */
    #[ORM\OneToMany(targetEntity: Cart::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $carts;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $orders;

    public function __construct()
    {
        parent::__construct();

        $this->carts = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->uuid = random_bytes(16);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string|int $id): static
    {
        $this->id = (int) $id;

        return $this;
    }

    #[Groups(['user:read', 'order:read', 'cart:read'])]
    public function getEmail(): string
    {
        return parent::getEmail();
    }

    public function setEmail(string $email): static
    {
        parent::setEmail($email);

        return $this;
    }

    #[Groups(['user:read', 'order:read', 'cart:read'])]
    public function getCreatedAt(): \DateTimeImmutable
    {
        return parent::getCreatedAt();
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

    public function getUsername(): ?string
    {
        return $this->name;
    }

    public function setUsername(?string $username): static
    {
        if ($username !== null) {
            $this->name = $username;
        }

        return $this;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getUuidAsString(): string
    {
        return bin2hex($this->uuid);
    }

    /**
     * @return Collection<int, Cart>
     */
    public function getCarts(): Collection
    {
        return $this->carts;
    }

    public function addCart(Cart $cart): static
    {
        if (!$this->carts->contains($cart)) {
            $this->carts->add($cart);
            $cart->setUser($this);
        }

        return $this;
    }

    public function removeCart(Cart $cart): static
    {
        if ($this->carts->removeElement($cart) && $cart->getUser() === $this) {
            $cart->setUser(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setUser($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order) && $order->getUser() === $this) {
            $order->setUser(null);
        }

        return $this;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function setTwoFactorEnabled(bool $twoFactorEnabled): static
    {
        $this->twoFactorEnabled = $twoFactorEnabled;

        return $this;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    public function setTwoFactorSecret(?string $twoFactorSecret): static
    {
        $this->twoFactorSecret = $twoFactorSecret;

        return $this;
    }

    public function getMagicLinkToken(): ?string
    {
        return $this->magicLinkToken;
    }

    public function setMagicLinkToken(?string $magicLinkToken): static
    {
        $this->magicLinkToken = $magicLinkToken;

        return $this;
    }

    public function getMagicLinkExpiresAt(): ?\DateTimeImmutable
    {
        return $this->magicLinkExpiresAt;
    }

    public function setMagicLinkExpiresAt(?\DateTimeImmutable $magicLinkExpiresAt): static
    {
        $this->magicLinkExpiresAt = $magicLinkExpiresAt;

        return $this;
    }
}
