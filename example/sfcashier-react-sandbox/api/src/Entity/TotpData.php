<?php

declare(strict_types=1);

namespace App\Entity;

use BetterAuth\Core\Entities\TotpData as BaseTotpData;
use Doctrine\ORM\Mapping as ORM;

/**
 * TotpData entity - Extends BetterAuth base TotpData.
 *
 * Used for Two-Factor Authentication (2FA) with TOTP.
 */
#[ORM\Entity]
#[ORM\Table(name: 'totp_data')]
class TotpData extends BaseTotpData
{
    // Add custom fields here if needed
}
