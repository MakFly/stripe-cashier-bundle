<?php

declare(strict_types=1);

namespace App\Service\Request;

use Symfony\Component\HttpFoundation\Request;

class LocaleResolver
{
    public function resolve(Request $request): string
    {
        $acceptLanguage = strtolower((string) $request->headers->get('Accept-Language', ''));
        if ($acceptLanguage === '') {
            return 'en';
        }

        $candidates = preg_split('/\s*,\s*/', $acceptLanguage) ?: [];
        foreach ($candidates as $candidate) {
            $locale = trim(explode(';', $candidate)[0] ?? '');
            if ($locale === '') {
                continue;
            }

            if (str_starts_with($locale, 'fr')) {
                return 'fr';
            }

            if (str_starts_with($locale, 'en')) {
                return 'en';
            }
        }

        return 'en';
    }
}
