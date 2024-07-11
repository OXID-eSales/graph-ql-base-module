<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Exception;

class FingerprintValidationException extends Error
{
    public function getCategory(): string
    {
        return ErrorCategories::REQUESTERROR;
    }
}
