<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Service;

use Lcobucci\JWT\UnencryptedToken;
use OxidEsales\GraphQL\Base\Exception\InvalidToken;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\GraphQL\Base\Infrastructure\Token as TokenInfrastructure;

/**
 * Token data access service
 */
class TokenValidator
{
    /** @var JwtConfigurationBuilder */
    private $jwtConfigurationBuilder;

    /** @var Legacy */
    private $legacyInfrastructure;

    /** @var TokenInfrastructure */
    private $tokenInfrastructure;

    public function __construct(
        JwtConfigurationBuilder $jwtConfigurationBuilder,
        Legacy $legacyInfrastructure,
        TokenInfrastructure $tokenInfrastructure
    ) {
        $this->jwtConfigurationBuilder = $jwtConfigurationBuilder;
        $this->legacyInfrastructure = $legacyInfrastructure;
        $this->tokenInfrastructure = $tokenInfrastructure;
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
        if (!$this->areConstraintsValid($token)) {
            throw InvalidToken::invalidToken();
        }

        if (!$token->claims()->get(Token::CLAIM_USER_ANONYMOUS) && !$this->isRegistered($token)) {
            throw InvalidToken::unknownToken();
        }

        if ($this->isUserBlocked($token->claims()->get(Token::CLAIM_USERID))) {
            throw InvalidToken::userBlocked();
        }
    }

    private function areConstraintsValid(UnencryptedToken $token): bool
    {
        $config = $this->jwtConfigurationBuilder->getConfiguration();
        $validator = $config->validator();

        return $validator->validate($token, ...$config->validationConstraints());
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
