<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Codeception\Acceptance;

use OxidEsales\Facts\Facts;
use OxidEsales\GraphQL\Base\Tests\Codeception\AcceptanceTester;

class GraphQLCest
{
    public function testLoginWithInvalidCredentials(AcceptanceTester $I): void
    {
        $I->sendGQLQuery('query {token(username:"wrong", password:"wrong")}');
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::UNAUTHORIZED);
        $I->seeResponseIsJson();
        $I->seeResponseContains('{"category":"permissionerror"}');
    }

    public function testLoginWithValidCredentials(AcceptanceTester $I): void
    {
        $password = 'user';

        $facts = new Facts();

        if ($facts->isEnterprise()) {
            $password = 'useruser';
        }

        $I->login('user@oxid-esales.com', $password);
    }

    public function testQueryWithInvalidToken(AcceptanceTester $I): void
    {
        $I->amBearerAuthenticated('invalid_token');
        $I->sendGQLQuery('query {token(username:"admin", password:"admin")}');
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::UNAUTHORIZED);
        $I->seeResponseIsJson();
        $I->seeResponseContains('errors');
        $I->seeResponseMatchesJsonType([
            'errors' => [
                [
                    'message'    => 'string:=The token is invalid',
                    'extensions' => [
                        'category' => 'string:=permissionerror',
                    ],
                ],
            ],
        ]);
        $I->canSeeHttpHeader('WWW-Authenticate', 'Bearer');
    }

    public function testQueryWithoutSkipSession(AcceptanceTester $I): void
    {
        $uri = '/widget.php?cl=graphql&lang=0&shp=1';

        $I->getRest()->haveHTTPHeader('Content-Type', 'application/json');
        $I->getRest()->sendPOST($uri, [
            'query'     => 'query {token(username:"admin", password:"admin")}',
            'variables' => [],
        ]);

        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::INTERNAL_SERVER_ERROR);
        $I->seeResponseIsJson();
        $I->seeResponseContains('errors');
        $I->seeResponseMatchesJsonType([
            'errors' => [
                [
                    'message'    => 'string:=Encountered unexpected running PHP session.',
                    'extensions' => [
                        'category' => 'string:=requesterror',
                    ],
                ],
            ],
        ]);
    }
}
