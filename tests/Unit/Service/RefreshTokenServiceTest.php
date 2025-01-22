<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Service;

use Lcobucci\JWT\UnencryptedToken;
use OxidEsales\GraphQL\Base\DataType\RefreshTokenInterface;
use OxidEsales\GraphQL\Base\DataType\UserInterface;
use OxidEsales\GraphQL\Base\Infrastructure\RefreshTokenRepositoryInterface;
use OxidEsales\GraphQL\Base\Service\FingerprintServiceInterface;
use OxidEsales\GraphQL\Base\Service\ModuleConfiguration;
use OxidEsales\GraphQL\Base\Service\RefreshTokenService;
use OxidEsales\GraphQL\Base\Service\RefreshTokenServiceInterface;
use OxidEsales\GraphQL\Base\Service\Token as TokenService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheCodingMachine\GraphQLite\Types\ID;

#[CoversClass(RefreshTokenService::class)]
class RefreshTokenServiceTest extends TestCase
{
    public function testRefreshTokenMethodGeneratesNewTokenByRefreshToken(): void
    {
        $refreshTokenService = $this->getSut(
            refreshTokRepo: $repositoryMock = $this->createMock(RefreshTokenRepositoryInterface::class),
            tokenService: $tokenServiceMock = $this->createMock(TokenService::class)
        );

        $refreshToken = uniqid();

        $repositoryMock->method('getTokenUser')->with($refreshToken)->willReturn(
            $userStub = $this->createStub(UserInterface::class)
        );

        $tokenServiceMock->method('createTokenForUser')->with($userStub)->willReturn(
            $this->createConfiguredStub(UnencryptedToken::class, [
                'toString' => $tokenValue = uniqid()
            ])
        );

        $this->assertSame($tokenValue, $refreshTokenService->refreshToken($refreshToken, uniqid()));
    }

    public function testRefreshTokenMethodTriggersTokenValidation(): void
    {
        $refreshTokenService = $this->getSut(
            fingerprintService: $fingerprintServiceSpy = $this->createMock(FingerprintServiceInterface::class),
        );

        $fingerprintHash = uniqid();

        $fingerprintServiceSpy->expects($this->once())
            ->method('validateFingerprintHashToCookie')->with($fingerprintHash);

        $refreshTokenService->refreshToken(uniqid(), $fingerprintHash);
    }

    public function testCreateRefreshTokenForUserTriggersExpiredTokensRemoval(): void
    {
        $refreshTokenService = $this->getSut(
            refreshTokRepo: $repositorySpy = $this->createMock(RefreshTokenRepositoryInterface::class),
        );

        $repositorySpy->expects($this->once())->method('removeExpiredTokens');

        $refreshTokenService->createRefreshTokenForUser($this->createStub(UserInterface::class));
    }

    public function testCreateRefreshReturnsRepositoryCreatedTokenValue(): void
    {
        $refreshTokenService = $this->getSut(
            refreshTokRepo: $repositoryMock = $this->createMock(RefreshTokenRepositoryInterface::class),
            moduleConfiguration: $this->createConfiguredStub(ModuleConfiguration::class, [
                'getRefreshTokenLifeTime' => $lifetime = uniqid()
            ]),
        );

        $userId = uniqid();
        $userStub = $this->createConfiguredStub(UserInterface::class, ['id' => new ID($userId)]);

        $repositoryMock->method('getNewRefreshToken')->with($userId, $lifetime)->willReturn(
            $this->createConfiguredStub(RefreshTokenInterface::class, [
                'token' => $newToken = uniqid()
            ])
        );

        $this->assertSame($newToken, $refreshTokenService->createRefreshTokenForUser($userStub));
    }

    public function getSut(
        RefreshTokenRepositoryInterface $refreshTokenRepository = null,
        ModuleConfiguration $moduleConfiguration = null,
        TokenService $tokenService = null,
        FingerprintServiceInterface $fingerprintService = null,
    ): RefreshTokenServiceInterface {
        return new RefreshTokenService(
            refreshTokenRepository: $refreshTokenRepository ?? $this->createStub(RefreshTokenRepositoryInterface::class),
            moduleConfiguration: $moduleConfiguration ?? $this->createStub(ModuleConfiguration::class),
            tokenService: $tokenService ?? $this->createStub(TokenService::class),
            fingerprintService: $fingerprintService ?? $this->createStub(FingerprintServiceInterface::class),
        );
    }
}
