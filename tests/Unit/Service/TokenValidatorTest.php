<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Framework;

use OxidEsales\GraphQL\Base\Exception\InvalidToken;
use OxidEsales\GraphQL\Base\Exception\TokenUserBlocked;
use OxidEsales\GraphQL\Base\Exception\UnknownToken;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy as LegacyService;
use OxidEsales\GraphQL\Base\Infrastructure\Token as TokenInfrastructure;
use OxidEsales\GraphQL\Base\Tests\Unit\BaseTestCase;

class TokenValidatorTest extends BaseTestCase
{
    public function testTokenShopIdValidation(): void
    {
        $legacy = $this->createPartialMock(LegacyService::class, ['login', 'getShopId', 'getShopUrl']);
        $legacy->method('login')->willReturn($this->getUserDataStub());
        $legacy->method('getShopId')->willReturn(1);

        $tokenInfrastructure = $this->createPartialMock(
            TokenInfrastructure::class,
            ['registerToken', 'isTokenRegistered', 'isTokenExpired', 'removeExpiredTokens', 'canIssueToken']
        );
        $tokenInfrastructure->method('isTokenRegistered')->willReturn(true);
        $tokenInfrastructure->method('canIssueToken')->willReturn(true);

        $token = $this->getTokenService($legacy, $tokenInfrastructure)->createToken('admin', 'admin');

        // token is valid
        $validator = $this->getTokenValidator($legacy, $tokenInfrastructure);
        $validator->validateToken($token);

        $legacy = $this->createPartialMock(LegacyService::class, ['getShopId', 'getShopUrl']);
        $legacy->method('getShopId')->willReturn(-1);

        // token is invalid
        $validator = $this->getTokenValidator($legacy, $tokenInfrastructure);
        $this->expectException(InvalidToken::class);
        $validator->validateToken($token);
    }

    public function testTokenShopUrlValidation(): void
    {
        $legacy = $this->createPartialMock(LegacyService::class, ['login', 'getShopUrl', 'getShopId']);
        $legacy->method('login')->willReturn($this->getUserDataStub());
        $legacy->method('getShopUrl')->willReturn('http://original.url');

        $tokenInfrastructure = $this->createPartialMock(
            TokenInfrastructure::class,
            ['registerToken', 'isTokenRegistered', 'isTokenExpired', 'removeExpiredTokens', 'canIssueToken']
        );
        $tokenInfrastructure->method('isTokenRegistered')->willReturn(true);
        $tokenInfrastructure->method('canIssueToken')->willReturn(true);

        $token = $this->getTokenService($legacy, $tokenInfrastructure)->createToken('admin', 'admin');

        // token is valid
        $validator = $this->getTokenValidator($legacy, $tokenInfrastructure);
        $validator->validateToken($token);

        $legacy = $this->createPartialMock(LegacyService::class, ['getShopUrl', 'getShopId']);
        $legacy->method('getShopUrl')->willReturn('http://other.url');

        // token is invalid
        $validator = $this->getTokenValidator($legacy, $tokenInfrastructure);
        $this->expectException(InvalidToken::class);
        $validator->validateToken($token);
    }

    public function testTokenUserInBlockedGroup(): void
    {
        $legacy = $this->createPartialMock(
            LegacyService::class,
            ['login', 'getShopId', 'getShopUrl', 'getUserGroupIds']
        );
        $legacy->method('login')->willReturn($this->getUserDataStub());
        $legacy->method('getUserGroupIds')->willReturn(['foo', 'oxidblocked', 'bar']);

        $tokenInfrastructure = $this->createPartialMock(
            TokenInfrastructure::class,
            ['registerToken', 'isTokenRegistered', 'isTokenExpired', 'removeExpiredTokens', 'canIssueToken']
        );
        $tokenInfrastructure->method('isTokenRegistered')->willReturn(true);
        $tokenInfrastructure->method('canIssueToken')->willReturn(true);

        $token = $this->getTokenService($legacy, $tokenInfrastructure)->createToken('admin', 'admin');

        $validator = $this->getTokenValidator($legacy, $tokenInfrastructure);
        $this->expectException(TokenUserBlocked::class);
        $validator->validateToken($token);
    }

    public function testExpiredToken(): void
    {
        $legacy = $this->createPartialMock(
            LegacyService::class,
            ['login', 'getShopId', 'getShopUrl', 'getUserGroupIds']
        );
        $legacy->method('login')->willReturn($this->getUserDataStub());
        $legacy->method('getUserGroupIds')->willReturn(['foo', 'bar']);

        $tokenInfrastructure = $this->createPartialMock(
            TokenInfrastructure::class,
            ['registerToken', 'isTokenRegistered', 'isTokenExpired', 'removeExpiredTokens', 'canIssueToken']
        );
        $tokenInfrastructure->method('isTokenRegistered')->willReturn(true);
        $tokenInfrastructure->method('canIssueToken')->willReturn(true);

        $validator = $this->getTokenValidator($legacy, $tokenInfrastructure);

        $token = $this->getTokenService($legacy, $tokenInfrastructure, null, '+1 hours')->createToken('admin', 'admin');
        $validator->validateToken($token);

        $token = $this->getTokenService($legacy, $tokenInfrastructure, null, '-1 hours')->createToken('admin', 'admin');
        $this->expectException(InvalidToken::class);
        $validator->validateToken($token);
    }

    public function testDeletedToken(): void
    {
        $legacy = $this->createPartialMock(
            LegacyService::class,
            ['login', 'getShopId', 'getShopUrl', 'getUserGroupIds']
        );
        $legacy->method('login')->willReturn($this->getUserDataStub());
        $legacy->method('getUserGroupIds')->willReturn(['foo', 'bar']);

        $tokenInfrastructure = $this->createPartialMock(
            TokenInfrastructure::class,
            ['registerToken', 'isTokenRegistered', 'isTokenExpired', 'removeExpiredTokens', 'canIssueToken']
        );
        $tokenInfrastructure->method('isTokenRegistered')->willReturn(false);
        $tokenInfrastructure->method('canIssueToken')->willReturn(true);

        $validator = $this->getTokenValidator($legacy, $tokenInfrastructure);

        $token = $this->getTokenService($legacy, $tokenInfrastructure, null, '+1 hours')->createToken('admin', 'admin');
        $this->expectException(UnknownToken::class);
        $validator->validateToken($token);
    }

    public function testAnonymousToken(): void
    {
        $legacy = $this->createPartialMock(
            LegacyService::class,
            ['login', 'getShopId', 'getShopUrl', 'getUserGroupIds']
        );
        $legacy->method('login')->willReturn($this->getUserDataStub(null, true));
        $legacy->method('getUserGroupIds')->willReturn(['oxidanonymous']);

        $tokenInfrastructure = $this->createPartialMock(
            TokenInfrastructure::class,
            ['registerToken', 'isTokenRegistered', 'isTokenExpired', 'removeExpiredTokens', 'canIssueToken']
        );
        $tokenInfrastructure->method('canIssueToken')->willReturn(true);
        $validator = $this->getTokenValidator($legacy, $tokenInfrastructure);

        // token is valid
        $token = $this->getTokenService($legacy, $tokenInfrastructure, null, '+1 hours')->createToken();
        $validator->validateToken($token);
    }
}
