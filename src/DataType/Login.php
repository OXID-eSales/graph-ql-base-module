<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\DataType;

use Lcobucci\JWT\UnencryptedToken;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type()
 */
final class Login implements LoginInterface
{
    public function __construct(
        private string $refreshToken,
        private UnencryptedToken $unencryptedToken
    ) {
    }

    /**
     * @Field()
     */
    public function refreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * @Field()
     */
    public function accessToken(): string
    {
        return $this->unencryptedToken->toString();
    }
}
