<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Service;

use Lcobucci\JWT\UnencryptedToken;
use OxidEsales\GraphQL\Base\DataType\UserInterface;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\GraphQL\Base\Service\LoginService;
use OxidEsales\GraphQL\Base\Service\RefreshTokenServiceInterface;
use OxidEsales\GraphQL\Base\Service\Token as TokenService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LoginService::class)]
class LoginServiceTest extends TestCase
{
    public function testLoginCreatesLoginInputTypeResult(): void
    {
        $loginService = new LoginService(
            legacyInfrastructure: $legacyInfrastructureMock = $this->createMock(Legacy::class),
            tokenService: $tokenServiceMock = $this->createMock(TokenService::class),
            refreshTokenService: $refreshTokenMock = $this->createMock(RefreshTokenServiceInterface::class),
        );

        $userName = uniqid();
        $password = uniqid();

        $legacyInfrastructureMock->method('login')
            ->with($userName, $password)
            ->willReturn($userType = $this->createStub(UserInterface::class));

        $refreshToken = uniqid();
        $refreshTokenMock->method('createRefreshTokenForUser')->with($userType)->willReturn($refreshToken);

        $accessToken = uniqid();
        $tokenServiceMock->method('createTokenForUser')->with($userType)->willReturn(
            $this->createConfiguredStub(UnencryptedToken::class, ['toString' => $accessToken])
        );

        $login = $loginService->login($userName, $password);

        $this->assertSame($refreshToken, $login->refreshToken());
        $this->assertSame($accessToken, $login->accessToken());
    }
}
