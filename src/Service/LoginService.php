<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Service;

use OxidEsales\GraphQL\Base\DataType\Login as LoginDatatype;
use OxidEsales\GraphQL\Base\DataType\LoginInterface;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;

/**
 * User login service
 */
class LoginService implements LoginServiceInterface
{
    public function __construct(
        private readonly Legacy $legacyInfrastructure,
        protected Token $tokenService,
        protected RefreshTokenServiceInterface $refreshTokenService,
    ) {
    }

    public function login(?string $userName, ?string $password): LoginInterface
    {
        $user = $this->legacyInfrastructure->login($userName, $password);

        return new LoginDatatype(
            refreshToken: $this->refreshTokenService->createRefreshTokenForUser($user),
            accessToken: $this->tokenService->createTokenForUser($user),
        );
    }
}
