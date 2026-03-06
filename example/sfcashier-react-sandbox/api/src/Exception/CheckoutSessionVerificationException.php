<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class CheckoutSessionVerificationException extends HttpException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(502, 'Unable to verify checkout session', $previous);
    }
}
