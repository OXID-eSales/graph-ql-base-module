<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Service;

class CookieService implements CookieServiceInterface
{
    public function setFingerprintCookie(string $fingerprint): void
    {
        setcookie(
            name: FingerprintServiceInterface::COOKIE_KEY,
            value: $fingerprint,
            httponly: true
        );
    }
}
