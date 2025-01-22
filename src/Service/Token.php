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
        private ?UnencryptedToken $unencryptedToken,
        private readonly JwtConfigurationBuilder $jwtConfigurationBuilder,
        private readonly Legacy $legacy,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ModuleConfiguration $moduleConfiguration,
        private readonly TokenInfrastructure $tokenInfrastructure
    ) {
    }

    public function getTokenClaim(string $claim, mixed $default = null): mixed
    {
        if (!$this->unencryptedToken instanceof UnencryptedToken) {
            return $default;
        }

        return $this->unencryptedToken->claims()->get($claim, $default);
    }

    public function getToken(): ?UnencryptedToken
    {
        return $this->unencryptedToken;
    }

    /**
     * @throws InvalidLogin
     * @throws TokenQuota
     */
    public function createToken(?string $username = null, ?string $password = null): UnencryptedToken
    {
        $user = $this->legacy->login($username, $password);

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
        $configuration = $this->jwtConfigurationBuilder->getConfiguration();

        $builder = $configuration->builder()
            ->issuedBy($this->legacy->getShopUrl())
            ->withHeader('iss', $this->legacy->getShopUrl())
            ->permittedFor($this->legacy->getShopUrl())
            ->issuedAt($time)
            ->canOnlyBeUsedAfter($time)
            ->expiresAt($expire)
            ->withClaim(self::CLAIM_SHOPID, $this->legacy->getShopId())
            ->withClaim(self::CLAIM_USERNAME, $user->email())
            ->withClaim(self::CLAIM_USERID, $user->id()->val())
            ->withClaim(self::CLAIM_USER_ANONYMOUS, $user->isAnonymous())
            ->withClaim(self::CLAIM_TOKENID, Legacy::createUniqueIdentifier());

        $beforeTokenCreation = new BeforeTokenCreation($builder, $user);
        $this->eventDispatcher->dispatch(
            $beforeTokenCreation
        );

        $plain = $beforeTokenCreation->getBuilder()->getToken(
            $configuration->signer(),
            $configuration->signingKey()
        );

        $this->registerToken($user, $plain, $time, $expire);

        return $plain;
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
        UnencryptedToken $unencryptedToken,
        DateTimeImmutable $time,
        DateTimeImmutable $expire
    ): void {
        if (!$user->isAnonymous()) {
            $this->tokenInfrastructure->registerToken($unencryptedToken, $time, $expire);
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
