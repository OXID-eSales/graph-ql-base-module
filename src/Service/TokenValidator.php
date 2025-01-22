<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Service;

use Lcobucci\JWT\UnencryptedToken;
use OxidEsales\GraphQL\Base\Exception\InvalidToken;
use OxidEsales\GraphQL\Base\Exception\TokenUserBlocked;
use OxidEsales\GraphQL\Base\Exception\UnknownToken;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\GraphQL\Base\Infrastructure\Token as TokenInfrastructure;

/**
 * Token data access service
 */
class TokenValidator
{
    public function __construct(
        private readonly JwtConfigurationBuilder $jwtConfigurationBuilder,
        private readonly Legacy $legacy,
        private readonly TokenInfrastructure $tokenInfrastructure
    ) {
    }

    /**
     * Checks if given token is valid:
     * - has valid signature
     * - has valid issuer and audience
     * - has valid shop claim
     * - token user is not in blocked group
     *
     * @throws InvalidToken
     *
     * @internal
     */
    public function validateToken(UnencryptedToken $unencryptedToken): void
    {
        if (!$this->areConstraintsValid($unencryptedToken) || $this->isTokenExpired($unencryptedToken)) {
            throw new InvalidToken();
        }

        if (!$unencryptedToken->claims()->get(Token::CLAIM_USER_ANONYMOUS) && !$this->isRegistered($unencryptedToken)) {
            throw new UnknownToken();
        }

        if ($this->isUserBlocked($unencryptedToken->claims()->get(Token::CLAIM_USERID))) {
            throw new TokenUserBlocked();
        }
    }

    private function areConstraintsValid(UnencryptedToken $unencryptedToken): bool
    {
        $configuration = $this->jwtConfigurationBuilder->getConfiguration();
        $validator = $configuration->validator();

        return $validator->validate($unencryptedToken, ...$configuration->validationConstraints());
    }

    private function isTokenExpired(UnencryptedToken $unencryptedToken): bool
    {
        return $this->tokenInfrastructure->isTokenExpired($unencryptedToken->claims()->get(Token::CLAIM_TOKENID));
    }

    private function isUserBlocked(?string $userId): bool
    {
        $groups = $this->legacy->getUserGroupIds($userId);

        if (in_array('oxidblocked', $groups)) {
            return true;
        }

        return false;
    }

    private function isRegistered(UnencryptedToken $unencryptedToken): bool
    {
        return $this->tokenInfrastructure->isTokenRegistered($unencryptedToken->claims()->get(Token::CLAIM_TOKENID));
    }
}
