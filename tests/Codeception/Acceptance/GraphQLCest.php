<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Codeception\Acceptance;

use OxidEsales\GraphQL\Base\Component\Widget\GraphQL;
use OxidEsales\GraphQL\Base\Tests\Codeception\AcceptanceTester;

/**
 * @group oe_graphql_base
 */
class GraphQLCest
{
    public function testLoginWithInvalidCredentials(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendGQLQuery('query {token(username:"wrong", password:"wrong")}');
        $acceptanceTester->seeResponseIsJson();
//        $I->seeResponseContains('{"category":"permissionerror"}');
        $acceptanceTester->canSeeHttpHeader('Server-Timing');
        $acceptanceTester->seeResponseContains('errors');

        $result = $acceptanceTester->grabJsonResponseAsArray();
        $acceptanceTester->assertEquals('Username/password combination is invalid', $result['errors'][0]['message']);
    }

    public function testLoginWithValidCredentials(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->login('user@oxid-esales.com', 'useruser');
        $acceptanceTester->canSeeHttpHeader('Server-Timing');
    }

    public function testQueryWithInvalidToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->amBearerAuthenticated('invalid_token');
        $acceptanceTester->sendGQLQuery('query {token(username:"admin", password:"admin")}');
        $acceptanceTester->seeResponseIsJson();
        $acceptanceTester->seeResponseContains('errors');
        $acceptanceTester->seeResponseMatchesJsonType([
            'errors' => [
                [
                    'message' => 'string:=Unable to parse token',
                ],
            ],
        ]);
        $acceptanceTester->canSeeHttpHeader('WWW-Authenticate', 'Bearer');
        $acceptanceTester->cantSeeHttpHeader('Server-Timing');
    }

    public function testQueryWithoutSkipSession(AcceptanceTester $acceptanceTester): void
    {
        $uri = '/widget.php?cl=graphql&lang=0&shp=1';

        $acceptanceTester->getRest()->haveHTTPHeader('Content-Type', 'application/json');
        $acceptanceTester->getRest()->sendPOST($uri, [
            'query' => 'query {token(username:"admin", password:"admin")}',
            'variables' => [],
        ]);

        $acceptanceTester->seeResponseIsJson();
        $acceptanceTester->seeResponseContains('errors');
        $acceptanceTester->seeResponseMatchesJsonType([
            'errors' => [
                [
                    'message' => 'string:=' . GraphQL::SESSION_ERROR_MESSAGE,
                ],
            ],
        ]);
        $acceptanceTester->cantSeeHttpHeader('Server-Timing');
    }

    public function testLoginAnonymousToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->login(null, null);
        $acceptanceTester->canSeeHttpHeader('Server-Timing');
    }
}
