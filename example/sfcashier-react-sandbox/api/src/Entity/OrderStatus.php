<?php

declare(strict_types=1);

namespace App\Entity;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case SHIPPED = 'shipped';
}
