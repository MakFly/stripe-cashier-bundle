<?php

declare(strict_types=1);

namespace App\Entity;

use BetterAuth\Core\Entities\PasswordResetToken as BasePasswordResetToken;
use Doctrine\ORM\Mapping as ORM;

/**
 * PasswordResetToken entity - Extends BetterAuth base PasswordResetToken.
 *
 * Used for password reset functionality.
 */
#[ORM\Entity]
#[ORM\Table(name: 'password_reset_tokens')]
class PasswordResetToken extends BasePasswordResetToken
{
    // Add custom fields here if needed
}
