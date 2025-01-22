<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Codeception\Acceptance;

use OxidEsales\GraphQL\Base\Tests\Codeception\AcceptanceTester;

/**
 * @group oe_graphql_base
 */
class LoginCest
{
    private const ADMIN_LOGIN = 'noreply@oxid-esales.com';

    private const ADMIN_PASSWORD = 'admin';

    public function testLoginWithMissingCredentials(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendGQLQuery('query { token }'); // anonymous token
        $result = $acceptanceTester->grabJsonResponseAsArray();

        $acceptanceTester->assertNotEmpty($result['data']['token']);
    }

    public function testLoginWithIncompleteCredentialsPassword(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendGQLQuery('query { token (username: "foo") }'); // anonymous token
        $result = $acceptanceTester->grabJsonResponseAsArray();

        $acceptanceTester->assertNotEmpty($result['data']['token']);
    }

    public function testLoginWithIncompleteCredentialsUsername(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendGQLQuery('query { token (password: "foo") }'); // anonymous token
        $result = $acceptanceTester->grabJsonResponseAsArray();

        $acceptanceTester->assertNotEmpty($result['data']['token']);
    }

    public function testLoginWithWrongCredentials(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendGQLQuery('query { token (username: "foo", password: "bar") }');
        $result = $acceptanceTester->grabJsonResponseAsArray();

        $acceptanceTester->assertEquals('Username/password combination is invalid', $result['errors'][0]['message']);
    }

    public function testLoginWithValidCredentials(AcceptanceTester $acceptanceTester): void
    {
        $query = 'query { token (username: "' . self::ADMIN_LOGIN . '", password: "' . self::ADMIN_PASSWORD . '") }';
        $acceptanceTester->sendGQLQuery($query);
        $result = $acceptanceTester->grabJsonResponseAsArray();

        $acceptanceTester->assertNotEmpty($result['data']['token']);
    }

    public function testLoginWithValidCredentialsInVariables(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendGQLQuery(
            'query ($username: String!, $password: String!) { token (username: $username, password: $password) }',
            [
                'username' => self::ADMIN_LOGIN,
                'password' => self::ADMIN_PASSWORD,
            ]
        );
        $result = $acceptanceTester->grabJsonResponseAsArray();

        $acceptanceTester->assertNotEmpty($result['data']['token']);
    }
}
