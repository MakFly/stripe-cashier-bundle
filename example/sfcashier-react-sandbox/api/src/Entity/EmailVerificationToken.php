<?php

declare(strict_types=1);

namespace App\Entity;

use BetterAuth\Core\Entities\EmailVerificationToken as BaseEmailVerificationToken;
use Doctrine\ORM\Mapping as ORM;

/**
 * EmailVerificationToken entity - Extends BetterAuth base EmailVerificationToken.
 *
 * Used for email verification during registration.
 */
#[ORM\Entity]
#[ORM\Table(name: 'email_verification_tokens')]
class EmailVerificationToken extends BaseEmailVerificationToken
{
    // Add custom fields here if needed
}
