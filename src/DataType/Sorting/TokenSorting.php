<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\DataType\Sorting;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
final class TokenSorting extends Sorting
{
    /**
     * Tokens will be sorted by their expiration date ('expires_at' column).
     */
    public function __construct(
        #[Field]
        private string $expiresAt = self::SORTING_ASC,
    ) {
        parent::__construct([
            'expires_at' => $this->expiresAt,
        ]);
    }
}
