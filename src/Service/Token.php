<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Service;

use DateTimeImmutable;
use Lcobucci\JWT\UnencryptedToken;
use OxidEsales\GraphQL\Base\DataType\UserInterface;
use OxidEsales\GraphQL\Base\Event\BeforeTokenCreation;
use OxidEsales\GraphQL\Base\Exception\InvalidLogin;
use OxidEsales\GraphQL\Base\Exception\TokenQuota;
use OxidEsales\GraphQL\Base\Exception\UnknownToken;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\GraphQL\Base\Infrastructure\Token as TokenInfrastructure;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Token data access service
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) TODO: Consider splitting this class
 */
class Token
{
    public const CLAIM_SHOPID = 'shopid';
    public const CLAIM_USERNAME = 'username';
    public const CLAIM_USERID = 'userid';
    public const CLAIM_USER_ANONYMOUS = 'useranonymous';
    public const CLAIM_TOKENID = 'tokenid';

    public function __construct(
        private ?UnencryptedToken $token,
        private readonly JwtConfigurationBuilder $jwtConfigBuilder,
        private readonly Legacy $legacyInfrastructure,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ModuleConfiguration $moduleConfiguration,
        private readonly TokenInfrastructure $tokenInfrastructure
    ) {
    }

    public function getTokenClaim(string $claim, mixed $default = null): mixed
    {
        if (!$this->token instanceof UnencryptedToken) {
            return $default;
        }

        return $this->token->claims()->get($claim, $default);
    }

    public function getToken(): ?UnencryptedToken
    {
        return $this->token;
    }

    /**
     * @throws InvalidLogin
     * @throws TokenQuota
     */
    public function createToken(?string $username = null, ?string $password = null): UnencryptedToken
    {
        $user = $this->legacyInfrastructure->login($username, $password);

        return $this->createTokenForUser($user);
    }

    /**
     * @throws TokenQuota
     */
    public function createTokenForUser(UserInterface $user): UnencryptedToken
    {
        $this->removeExpiredTokens($user);
        $this->canIssueToken($user);

        $time = new DateTimeImmutable('now');
        $expire = new DateTimeImmutable($this->moduleConfiguration->getTokenLifeTime());
        $config = $this->jwtConfigBuilder->getConfiguration();

        $builder = $config->builder()
            ->issuedBy($this->legacyInfrastructure->getShopUrl())
            ->withHeader('iss', $this->legacyInfrastructure->getShopUrl())
            ->permittedFor($this->legacyInfrastructure->getShopUrl())
            ->issuedAt($time)
            ->canOnlyBeUsedAfter($time)
            ->expiresAt($expire)
            ->withClaim(self::CLAIM_SHOPID, $this->legacyInfrastructure->getShopId())
            ->withClaim(self::CLAIM_USERNAME, $user->email())
            ->withClaim(self::CLAIM_USERID, $user->id()->val())
            ->withClaim(self::CLAIM_USER_ANONYMOUS, $user->isAnonymous())
            ->withClaim(self::CLAIM_TOKENID, $this->legacyInfrastructure->createUniqueIdentifier());

        $event = new BeforeTokenCreation($builder, $user);
        $this->eventDispatcher->dispatch(
            $event
        );

        $token = $event->getBuilder()->getToken(
            $config->signer(),
            $config->signingKey()
        );

        $this->registerToken($user, $token, $time, $expire);

        return $token;
    }

    public function deleteToken(ID $tokenId): void
    {
        $tokenId = (string)$tokenId;

        if (!$this->tokenInfrastructure->isTokenRegistered($tokenId)) {
            throw new UnknownToken();
        }

        $this->tokenInfrastructure->tokenDelete(null, $tokenId);
    }

    public function deleteUserToken(UserInterface $user, ID $tokenId): void
    {
        if (!$this->tokenInfrastructure->userHasToken($user, (string)$tokenId)) {
            throw new UnknownToken();
        }

        $this->tokenInfrastructure->tokenDelete($user, (string)$tokenId);
    }

    private function registerToken(
        UserInterface $user,
        UnencryptedToken $token,
        DateTimeImmutable $time,
        DateTimeImmutable $expire
    ): void {
        if (!$user->isAnonymous()) {
            $this->tokenInfrastructure->registerToken($token, $time, $expire);
        }
    }

    private function canIssueToken(UserInterface $user): void
    {
        if (
            !$user->isAnonymous() &&
            !$this->tokenInfrastructure->canIssueToken($user, $this->moduleConfiguration->getUserTokenQuota())
        ) {
            throw new TokenQuota();
        }
    }

    private function removeExpiredTokens(UserInterface $user): void
    {
        if (!$user->isAnonymous()) {
            $this->tokenInfrastructure->removeExpiredTokens($user);
        }
    }
}
