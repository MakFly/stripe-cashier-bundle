<?php

declare(strict_types=1);

namespace App\Entity;

use BetterAuth\Core\Entities\RefreshToken as BaseRefreshToken;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshToken extends BaseRefreshToken
{
    #[ORM\Column(type: 'integer')]
    protected int $userId;

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(string|int $userId): static
    {
        $this->userId = (int) $userId;

        return $this;
    }
}
