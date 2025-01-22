<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Service;

use Lcobucci\JWT\Token;
use OxidEsales\GraphQL\Base\Exception\InvalidLogin;
use OxidEsales\GraphQL\Base\Exception\TokenQuota;
use OxidEsales\GraphQL\Base\Exception\UnknownToken;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy as LegacyService;
use OxidEsales\GraphQL\Base\Infrastructure\Token as TokenInfrastructure;
use OxidEsales\GraphQL\Base\Service\Token as TokenService;
use OxidEsales\GraphQL\Base\Tests\Unit\BaseTestCase;
use TheCodingMachine\GraphQLite\Types\ID;

class TokenTest extends BaseTestCase
{
    public function testCreateTokenWithInvalidCredentials(): void
    {
        $legacy = $this->createPartialMock(LegacyService::class, ['login']);
        $legacy->method('login')->willThrowException(
            new InvalidLogin('Username/password combination is invalid')
        );

        $this->expectException(InvalidLogin::class);
        $this->getTokenService($legacy)->createToken('foo', 'bar');
    }

    public function testCreateTokenWithValidCredentials(): void
    {
        $legacy = $this->createPartialMock(
            LegacyService::class,
            ['login', 'getShopId', 'getShopUrl']
        );
        $legacy->method('login')->willReturn($this->getUserDataStub($this->getUserModelStub('the_admin_oxid')));

        $unencryptedToken = $this->getTokenService($legacy)->createToken('admin', 'admin');

        $this->assertInstanceOf(Token::class, $unencryptedToken);
    }

    public function testCreateTokenWithValidCredentialsForBlockedUser(): void
    {
        $legacy = $this->createPartialMock(
            LegacyService::class,
            ['login', 'getShopId', 'getShopUrl', 'getUserGroupIds']
        );
        $legacy->method('login')->willReturn($this->getUserDataStub($this->getUserModelStub('the_admin_oxid')));
        $legacy->method('getUserGroupIds')->willReturn(['foo', 'oxidblocked', 'bar']);

        $unencryptedToken = $this->getTokenService($legacy)->createToken('admin', 'admin');

        $this->assertInstanceOf(Token::class, $unencryptedToken);
    }

    public function testCreateAnonymousToken(): void
    {
        $legacy = $this->createPartialMock(
            LegacyService::class,
            ['login', 'getShopId', 'getShopUrl']
        );
        $legacy->method('login')->willReturn($this->getUserDataStub($this->getUserModelStub()));

        $unencryptedToken = $this->getTokenService($legacy)->createToken();

        $this->assertInstanceOf(Token::class, $unencryptedToken);
        $this->assertEmpty($unencryptedToken->claims()->get(TokenService::CLAIM_USERNAME));
    }

    public function testTokenQuotaExceeded(): void
    {
        $legacy = $this->createPartialMock(
            LegacyService::class,
            ['login', 'getShopId', 'getShopUrl']
        );
        $legacy->method('login')->willReturn($this->getUserDataStub($this->getUserModelStub()));

        $tokenInfrastructure = $this->createPartialMock(
            TokenInfrastructure::class,
            ['canIssueToken', 'removeExpiredTokens']
        );
        $tokenInfrastructure->method('canIssueToken')->willReturn(false);

        $this->expectException(TokenQuota::class);
        $this->getTokenService($legacy, $tokenInfrastructure)->createToken('admin', 'admin');
    }

    public function testDeleteToken(): void
    {
        $tokenId = 'not_existing';

        $legacy = $this->createMock(LegacyService::class);

        $tokenInfrastructure = $this->createPartialMock(
            TokenInfrastructure::class,
            ['isTokenRegistered', 'tokenDelete']
        );
        $tokenInfrastructure->method('isTokenRegistered')->willReturn(true);
        $tokenInfrastructure->expects($this->once())->method('tokenDelete')->with(null, $tokenId);

        $this->getTokenService($legacy, $tokenInfrastructure)->deleteToken(new ID($tokenId));
    }

    public function testDeleteNotExistingToken(): void
    {
        $legacy = $this->createMock(LegacyService::class);

        $this->expectException(UnknownToken::class);
        $this->expectExceptionMessage('The token is not registered');

        $this->getTokenService($legacy)->deleteToken(new ID('not_existing'));
    }

    public function testDeleteUserToken(): void
    {
        $tokenId = 'not_existing';
        $user = $this->getUserDataStub($this->getUserModelStub('_testuser'));
        $legacy = $this->createMock(LegacyService::class);

        $tokenInfrastructure = $this->createPartialMock(
            TokenInfrastructure::class,
            ['userHasToken', 'tokenDelete']
        );
        $tokenInfrastructure->method('userHasToken')->willReturn(true);
        $tokenInfrastructure->expects($this->once())->method('tokenDelete')->with($user, $tokenId);

        $this->getTokenService($legacy, $tokenInfrastructure)->deleteUserToken($user, new ID($tokenId));
    }

    public function testDeleteNotExistingUserToken(): void
    {
        $user = $this->getUserDataStub($this->getUserModelStub('_testuser'));
        $legacy = $this->createMock(LegacyService::class);
        $tokenInfrastructure = $this->createPartialMock(TokenInfrastructure::class, ['userHasToken']);
        $tokenInfrastructure->method('userHasToken')->willReturn(false);

        $this->expectException(UnknownToken::class);
        $this->expectExceptionMessage('The token is not registered');

        $this->getTokenService($legacy, $tokenInfrastructure)->deleteUserToken($user, new ID('not_existing'));
    }
}
