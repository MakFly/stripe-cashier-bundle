<?php

declare(strict_types=1);

namespace App\Service\Request;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

class FrontendOriginResolver
{
    public function __construct(
        #[Autowire(env: 'FRONTEND_URL')]
        private readonly string $frontendUrl,
    ) {}

    public function resolve(Request $request): string
    {
        $origin = $request->headers->get('origin');
        if (is_string($origin) && trim($origin) !== '') {
            return rtrim(trim($origin), '/');
        }

        return rtrim($this->frontendUrl, '/');
    }
}
