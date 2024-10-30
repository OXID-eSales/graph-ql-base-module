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
        private readonly JwtConfigurationBuilder $jwtConfigBuilder,
        private readonly Legacy $legacyInfrastructure,
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
    public function validateToken(UnencryptedToken $token): void
    {
        if (!$this->areConstraintsValid($token) || $this->isTokenExpired($token)) {
            throw new InvalidToken();
        }

        if (!$token->claims()->get(Token::CLAIM_USER_ANONYMOUS) && !$this->isRegistered($token)) {
            throw new UnknownToken();
        }

        if ($this->isUserBlocked($token->claims()->get(Token::CLAIM_USERID))) {
            throw new TokenUserBlocked();
        }
    }

    private function areConstraintsValid(UnencryptedToken $token): bool
    {
        $config = $this->jwtConfigBuilder->getConfiguration();
        $validator = $config->validator();

        return $validator->validate($token, ...$config->validationConstraints());
    }

    private function isTokenExpired(UnencryptedToken $token): bool
    {
        return $this->tokenInfrastructure->isTokenExpired($token->claims()->get(Token::CLAIM_TOKENID));
    }

    private function isUserBlocked(?string $userId): bool
    {
        $groups = $this->legacyInfrastructure->getUserGroupIds($userId);

        if (in_array('oxidblocked', $groups)) {
            return true;
        }

        return false;
    }

    private function isRegistered(UnencryptedToken $token): bool
    {
        return $this->tokenInfrastructure->isTokenRegistered($token->claims()->get(Token::CLAIM_TOKENID));
    }
}
