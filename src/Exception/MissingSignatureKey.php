<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Exception;

class MissingSignatureKey extends Error
{
    protected const WRONG_SIZE_MESSAGE = 'Signature key is too short';

    public function __construct()
    {
        parent::__construct(self::WRONG_SIZE_MESSAGE);
    }
}
