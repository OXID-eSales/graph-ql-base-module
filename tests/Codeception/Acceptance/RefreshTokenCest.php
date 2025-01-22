<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Codeception\Acceptance;

use Codeception\Attribute\Group;
use OxidEsales\GraphQL\Base\Service\FingerprintService;
use OxidEsales\GraphQL\Base\Service\Token;
use OxidEsales\GraphQL\Base\Tests\Codeception\AcceptanceTester;

#[Group("oe_graphql_base")]
#[Group("oe_graphql_base_token")]
class RefreshTokenCest
{
    private const ADMIN_LOGIN = 'noreply@oxid-esales.com';
    private const ADMIN_PASSWORD = 'admin';

    public function testRefreshAccessToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendGQLQuery(
            'query ($username: String!, $password: String!) { login (username: $username, password: $password)
                {
                    accessToken
                    refreshToken
                }
            }',
            [
                'username' => self::ADMIN_LOGIN,
                'password' => self::ADMIN_PASSWORD,
            ]
        );

        $result = $acceptanceTester->grabJsonResponseAsArray();
        $acceptanceTester->assertNotEmpty($result['data']['login']['accessToken']);
        $acceptanceTester->assertNotEmpty($result['data']['login']['refreshToken']);

        $accessToken = $acceptanceTester->parseJwt($result['data']['login']['accessToken']);
        $fingerprintHash = $accessToken->claims()->get(FingerprintService::TOKEN_KEY);
        $cookie = $acceptanceTester->grabCookies()->get(FingerprintService::COOKIE_KEY)->getRawValue();

        $acceptanceTester->assertEquals(self::ADMIN_LOGIN, $accessToken->claims()->get(Token::CLAIM_USERNAME));
        $acceptanceTester->assertNotEmpty($fingerprintHash);
        $acceptanceTester->assertEquals(128, strlen($cookie));
        $acceptanceTester->assertFalse($accessToken->claims()->get(Token::CLAIM_USER_ANONYMOUS));

        $refreshToken = $result['data']['login']['refreshToken'];

        $acceptanceTester->sendGQLQuery(
            'query ($refreshToken: String!, $fingerprintHash: String!) {
                refresh (refreshToken: $refreshToken, fingerprintHash: $fingerprintHash)
            }',
            [
                'refreshToken' => $refreshToken,
                'fingerprintHash' => $fingerprintHash
            ]
        );
        $result = $acceptanceTester->grabJsonResponseAsArray();

        $acceptanceTester->assertNotEmpty($result['data']['refresh']);

        $accessToken = $acceptanceTester->parseJwt($result['data']['refresh']);
        $newFingerprint = $accessToken->claims()->get(FingerprintService::TOKEN_KEY);
        $newCookie = $acceptanceTester->grabCookies()->get(FingerprintService::COOKIE_KEY)->getRawValue();

        $acceptanceTester->assertFalse($accessToken->claims()->get(Token::CLAIM_USER_ANONYMOUS));

        $acceptanceTester->assertNotEquals($fingerprintHash, $newFingerprint);

        $acceptanceTester->assertNotEquals($cookie, $newCookie);
    }
}
