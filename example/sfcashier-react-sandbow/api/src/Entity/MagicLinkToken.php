<?php

declare(strict_types=1);

namespace App\Entity;

use BetterAuth\Core\Entities\MagicLinkToken as BaseMagicLinkToken;
use Doctrine\ORM\Mapping as ORM;

/**
 * MagicLinkToken entity - Extends BetterAuth base MagicLinkToken.
 *
 * Used for passwordless authentication via email magic links.
 */
#[ORM\Entity]
#[ORM\Table(name: 'magic_link_tokens')]
class MagicLinkToken extends BaseMagicLinkToken
{
    // Add custom fields here if needed
}
