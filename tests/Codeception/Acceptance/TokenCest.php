<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Codeception\Acceptance;

use DateTimeImmutable;
use OxidEsales\GraphQL\Base\Tests\Codeception\AcceptanceTester;

/**
 * @group oe_graphql_base
 * @group oe_graphql_base_token
 */
class TokenCest
{
    private const TEST_USER_ID = 'e7af1c3b786fd02906ccd75698f4e6b9';

    private const ADMIN_LOGIN = 'noreply@oxid-esales.com';

    private const ADMIN_PASSWORD = 'admin';

    private const USER_LOGIN = 'user@oxid-esales.com';

    public function _before(AcceptanceTester $acceptanceTester): void
    {
        $this->adminDeletesAllUserTokens($acceptanceTester);
    }

    public function _after(AcceptanceTester $acceptanceTester): void
    {
        $this->adminDeletesAllUserTokens($acceptanceTester);
    }

    public function testCannotQueryTokensWithoutToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('cannot query tokens without token');

        $result = $this->sendTokenQuery($acceptanceTester);

        $acceptanceTester->assertStringStartsWith(
            'You need to be logged to access this field',
            $result['errors'][0]['message']
        );
    }

    public function testCannotQueryTokensWithAnonymousToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('cannot query tokens with anonymous token');

        $acceptanceTester->sendGQLQuery('query { token }'); // anonymous token
        $result = $acceptanceTester->grabJsonResponseAsArray();
        $acceptanceTester->amBearerAuthenticated($result['data']['token']);

        $result = $this->sendTokenQuery($acceptanceTester);

        $acceptanceTester->assertStringStartsWith(
            'You need to be logged to access this field',
            $result['errors'][0]['message']
        );
    }

    public function testQueryTokensWithUserToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('standard customer can only query own tokens');

        $token = $this->generateUserTokens($acceptanceTester, false);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendTokenQuery($acceptanceTester);
        $tokenCountBefore = count($result['data']['tokens']);

        $token = $this->generateUserTokens($acceptanceTester, false);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendTokenQuery($acceptanceTester);
        $tokenCountAfter = count($result['data']['tokens']);

        //we see three more user tokens
        $acceptanceTester->assertEquals($tokenCountBefore + 3, $tokenCountAfter);
    }

    public function testQueryTokensWithAdminToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('special rights user will get only own tokens without filter');

        $token = $this->generateUserTokens($acceptanceTester, true);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendTokenQuery($acceptanceTester);
        $tokenCountBefore = count($result['data']['tokens']);

        $token = $this->generateUserTokens($acceptanceTester);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendTokenQuery($acceptanceTester);
        $tokenCountAfter = count($result['data']['tokens']);

        //we see two more user tokens because without explicit filter userid filter will be added by default
        $acceptanceTester->assertEquals($tokenCountBefore + 2, $tokenCountAfter);
        $acceptanceTester->assertNotEquals(self::TEST_USER_ID, $result['data']['tokens'][0]['customerId']); //admin user
    }

    public function testQueryTokensWithAdminTokenAndUserFilterOnNotExistingUserId(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('special rights user will get no tokens for filter on not existing user id');

        $filterPart = '( filter: {
                           customerId: {
                               equals: "not_existing"
                          }
                        })';

        $token = $this->generateUserTokens($acceptanceTester);
        $acceptanceTester->amBearerAuthenticated($token);
        $result = $this->sendTokenQuery($acceptanceTester, $filterPart);

        $acceptanceTester->assertEmpty($result['data']['tokens']);
    }

    public function testQueryTokensWithUserTokenAndUserFilterOnNotOwnId(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('normal user with filter on other user id');

        $filterPart = '( filter: {
                           customerId: {
                               equals: "not_existing_id"
                          }
                        })';

        $token = $this->generateUserTokens($acceptanceTester, false);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendTokenQuery($acceptanceTester, $filterPart);

        $acceptanceTester->assertStringStartsWith('Unauthorized', $result['errors'][0]['message']);
    }

    public function testQueryTokensWithUserTokenAndUserFilterOnOwnId(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('normal user with filter on own user id');

        $filterPart = '( filter: {
                           customerId: {
                               equals: "' . self::TEST_USER_ID . '"
                          }
                        })';

        $token = $this->generateUserTokens($acceptanceTester, false);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendTokenQuery($acceptanceTester, $filterPart);
        $tokenCountBefore = count($result['data']['tokens']);

        $token = $this->generateUserTokens($acceptanceTester);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendTokenQuery($acceptanceTester, $filterPart);
        $tokenCountAfter = count($result['data']['tokens']);

        //we see three more user tokens for this customer
        $acceptanceTester->assertEquals(self::TEST_USER_ID, $result['data']['tokens'][0]['customerId']);
        $acceptanceTester->assertEquals($tokenCountBefore + 3, $tokenCountAfter);
    }

    public function testQueryTokensWithAdminTokenAndUserFilter(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('special rights will get other user tokens with filter');

        $filterPart = '( filter: {
                           customerId: {
                               equals: "' . self::TEST_USER_ID . '"
                          }
                        })';

        $token = $this->generateUserTokens($acceptanceTester);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendTokenQuery($acceptanceTester, $filterPart);
        $tokenCountBefore = count($result['data']['tokens']);

        $token = $this->generateUserTokens($acceptanceTester);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendTokenQuery($acceptanceTester, $filterPart);
        $tokenCountAfter = count($result['data']['tokens']);

        //we see three more user tokens because of userid filter
        $acceptanceTester->assertEquals(self::TEST_USER_ID, $result['data']['tokens'][0]['customerId']);
        $acceptanceTester->assertEquals($tokenCountBefore + 3, $tokenCountAfter);
    }

    public function testQueryTokensWithSorting(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('tokens query with sorting');

        $this->generateUserTokens($acceptanceTester, false, true);
        $token = $this->generateUserTokens($acceptanceTester, false, true);
        $acceptanceTester->amBearerAuthenticated($token);

        $resultDESC = $this->sendTokenQuery($acceptanceTester, '(sort:{expiresAt: "DESC"})');
        $resultASC = $this->sendTokenQuery($acceptanceTester, '(sort:{expiresAt: "ASC"})');

        $acceptanceTester->assertEquals(count($resultASC['data']['tokens']), count($resultDESC['data']['tokens']));
        $acceptanceTester->assertNotEquals($resultDESC['data']['tokens'], $resultASC['data']['tokens']);
        $acceptanceTester->assertLessThan(
            $resultDESC['data']['tokens'][0]['expiresAt'],
            $resultASC['data']['tokens'][0]['expiresAt']
        );
    }

    public function testQueryTokensWithPagination(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('tokens query with pagination');

        $this->generateUserTokens($acceptanceTester);
        $token = $this->generateUserTokens($acceptanceTester);
        $acceptanceTester->amBearerAuthenticated($token);

        $resultFirst = $this->sendTokenQuery($acceptanceTester, "(pagination:{offset: 0 \n limit: 3})");
        $resultSecond = $this->sendTokenQuery($acceptanceTester, "(pagination:{offset: 1 \n limit: 3})");

        $acceptanceTester->assertEquals(count($resultFirst['data']['tokens']), count($resultSecond['data']['tokens']));
        $acceptanceTester->assertNotEquals($resultFirst['data']['tokens'], $resultSecond['data']['tokens']);
        $acceptanceTester->assertEquals($resultFirst['data']['tokens'][1]['id'], $resultSecond['data']['tokens'][0]['id']);
    }

    public function testQueryTokensWithShopIdFilter(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('tokens query with shopid filter');

        $token = $this->generateUserTokens($acceptanceTester);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendTokenQuery($acceptanceTester, '(filter:{shopId:{equals: "1"}})');
        $acceptanceTester->assertNotEmpty($result['data']['tokens']);

        $result = $this->sendTokenQuery($acceptanceTester, '(filter:{shopId:{equals: "666"}})');
        $acceptanceTester->assertEmpty($result['data']['tokens']);
    }

    public function testQueryTokensWithDateFilter(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('tokens query with date filter');

        $token = $this->generateUserTokens($acceptanceTester, false);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendTokenQuery(
            $acceptanceTester,
            '(filter:{expiresAt:{between: ["2020-12-01 12:12:12", "2021-12-01 12:12:12"]}})'
        );
        $acceptanceTester->assertEmpty($result['data']['tokens']);

        $filterPart = '(filter:{expiresAt:{between: ["2020-12-01 12:12:12", "' .
            (new DateTimeImmutable('+48 hours'))->format('Y-m-d H:i:s') . '"]}})';
        $result = $this->sendTokenQuery($acceptanceTester, $filterPart);
        $acceptanceTester->assertNotEmpty($result['data']['tokens']);
    }

    public function testCustomerTokensDeleteWithoutToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling customerTokenDelete without token');

        $result = $this->sendCustomerTokenDeleteMutation($acceptanceTester);

        $acceptanceTester->assertStringStartsWith('You need to be logged to access this field', $result['errors'][0]['message']);
    }

    public function testCustomerTokensDeleteWithAnonymousToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling customerTokenDelete with anonymous token');

        $acceptanceTester->sendGQLQuery('query { token }');
        $token = $acceptanceTester->grabJsonResponseAsArray()['data']['token'];
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendCustomerTokenDeleteMutation($acceptanceTester);

        $acceptanceTester->assertStringStartsWith(
            'You need to be logged to access this field',
            $result['errors'][0]['message']
        );
    }

    public function testCustomerTokensDeleteDefault(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling customerTokenDelete as normal user without customer id');

        $token = $this->generateUserTokens($acceptanceTester, false);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendCustomerTokenDeleteMutation($acceptanceTester);

        $acceptanceTester->assertEquals(3, $result['data']['customerTokensDelete']);
    }

    public function testCustomerTokensDeleteOwnId(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling customerTokenDelete as normal user with own id');

        $token = $this->generateUserTokens($acceptanceTester, false);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendCustomerTokenDeleteMutation($acceptanceTester, self::TEST_USER_ID);

        $acceptanceTester->assertEquals(3, $result['data']['customerTokensDelete']);
    }

    public function testCustomerTokensDeleteOtherUserFails(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling customerTokenDelete as normal user with other customer id');

        $token = $this->generateUserTokens($acceptanceTester, false);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendCustomerTokenDeleteMutation($acceptanceTester, '_other_user');

        $acceptanceTester->assertStringStartsWith('Unauthorized', $result['errors'][0]['message']);
    }

    public function testCustomerTokensDeleteOtherUserAdmin(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling customerTokenDelete as special rights user with other customer id');

        $token = $this->generateUserTokens($acceptanceTester);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendCustomerTokenDeleteMutation($acceptanceTester, self::TEST_USER_ID);

        $acceptanceTester->assertEquals(3, $result['data']['customerTokensDelete']);
    }

    public function testCustomerTokensDeleteAdminDeletesOwnTokens(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling customerTokenDelete as special rights user without customer id');

        $token = $this->generateUserTokens($acceptanceTester);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendCustomerTokenDeleteMutation($acceptanceTester);

        $acceptanceTester->assertEquals(2, $result['data']['customerTokensDelete']);
    }

    public function testCustomerTokensDeleteNotExistingOtherUserAdmin(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling customerTokenDelete as special rights user for not existing customer');

        $token = $this->generateUserTokens($acceptanceTester);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendCustomerTokenDeleteMutation($acceptanceTester, 'unknown_user');

        $acceptanceTester->assertStringStartsWith('User was not found by id:', $result['errors'][0]['message']);
    }

    public function testNotLoggedUserCannotDeleteToken(AcceptanceTester $acceptanceTester): void
    {
        $response = $this->sendTokenDeleteMutation($acceptanceTester, 'not_existing_token');
        $acceptanceTester->assertEquals('You need to be logged to access this field', $response['errors'][0]['message']);
    }

    public function testAnonymousUserCannotDeleteToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->login();
        $response = $this->sendTokenDeleteMutation($acceptanceTester, 'not_existing_token');
        $acceptanceTester->assertEquals('You need to be logged to access this field', $response['errors'][0]['message']);
    }

    public function testShopAdminCannotDeleteNotExistingToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->login(self::ADMIN_LOGIN, self::ADMIN_PASSWORD);
        $response = $this->sendTokenDeleteMutation($acceptanceTester, 'not_existing_token');
        $acceptanceTester->assertEquals('The token is not registered', $response['errors'][0]['message']);
    }

    public function testAdminCanDeleteToken(AcceptanceTester $acceptanceTester): void
    {
        $token = $this->generateUserTokens($acceptanceTester);
        $acceptanceTester->amBearerAuthenticated($token);

        $filterPart = '( filter: {
                           customerId: {
                               equals: "' . self::TEST_USER_ID . '"
                          }
                        })';

        // Get one of user tokens
        $response = $this->sendTokenQuery($acceptanceTester, $filterPart);
        $tokenId = $response['data']['tokens'][0]['id'];

        // Delete it
        $response = $this->sendTokenDeleteMutation($acceptanceTester, $tokenId);
        $acceptanceTester->assertTrue($response['data']['tokenDelete']);

        // It's not there anymore
        $response = $this->sendTokenQuery($acceptanceTester, $filterPart);
        $ids = array_map(function ($tokenRow) {
            return $tokenRow['id'];
        }, $response['data']['tokens']);
        $acceptanceTester->assertNotContains($tokenId, $ids);
    }

    public function testUserCannotDeleteNotExistingToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->login(self::USER_LOGIN, $this->getUserPassword());
        $response = $this->sendTokenDeleteMutation($acceptanceTester, 'not_existing_token');
        $acceptanceTester->assertEquals('The token is not registered', $response['errors'][0]['message']);
    }

    public function testUserCanDeleteHisToken(AcceptanceTester $acceptanceTester): void
    {
        // Generate two tokens
        $acceptanceTester->login(self::USER_LOGIN, $this->getUserPassword());
        sleep(1);
        $acceptanceTester->login(self::USER_LOGIN, $this->getUserPassword());

        $response = $this->sendTokenQuery($acceptanceTester, '(sort:{expiresAt: "ASC"})');
        $tokenId = $response['data']['tokens'][0]['id'];

        // Delete the older one
        $response = $this->sendTokenDeleteMutation($acceptanceTester, $tokenId);
        $acceptanceTester->assertTrue($response['data']['tokenDelete']);

        $response = $this->sendTokenQuery($acceptanceTester);
        $acceptanceTester->assertNotEquals($tokenId, $response['data']['tokens'][0]['id']);
    }

    public function testUserCannotDeleteNotHisToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->login(self::ADMIN_LOGIN, self::ADMIN_PASSWORD);
        $response = $this->sendTokenQuery($acceptanceTester);
        $tokenId = $response['data']['tokens'][0]['id'];

        $acceptanceTester->login(self::USER_LOGIN, $this->getUserPassword());
        $response = $this->sendTokenDeleteMutation($acceptanceTester, $tokenId);
        $acceptanceTester->assertEquals('The token is not registered', $response['errors'][0]['message']);
    }

    public function testShopTokensDeleteWithoutToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling shopTokenDelete without token');

        $result = $this->sendShopTokensDeleteMutation($acceptanceTester);

        $acceptanceTester->assertStringStartsWith(
            'You need to be logged to access this field',
            $result['errors'][0]['message']
        );
    }

    public function testShopTokensDeleteWithAnonymousToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling shopTokenDelete with anonymous token');

        $acceptanceTester->sendGQLQuery('query { token }');
        $token = $acceptanceTester->grabJsonResponseAsArray()['data']['token'];
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendShopTokensDeleteMutation($acceptanceTester);

        $acceptanceTester->assertStringStartsWith(
            'You need to be logged to access this field',
            $result['errors'][0]['message']
        );
    }

    public function testShopTokensDeleteWithUserToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling shopTokenDelete with user token');

        $token = $this->generateUserTokens($acceptanceTester, false);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendShopTokensDeleteMutation($acceptanceTester);

        $acceptanceTester->assertStringStartsWith(
            'You do not have sufficient rights to access this field',
            $result['errors'][0]['message']
        );
    }

    public function testShopTokensDeleteWithAdminToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling shopTokenDelete with special rights token');

        $token = $this->generateUserTokens($acceptanceTester);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendShopTokensDeleteMutation($acceptanceTester);

        $acceptanceTester->assertEquals(5, $result['data']['shopTokensDelete']);
    }

    public function testRegenerateSignatureKeyWithoutToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling regenerateSignatureKey without token');

        $result = $this->sendRegenerateSignatureKeyMutation($acceptanceTester);

        $acceptanceTester->assertStringStartsWith(
            'You need to be logged to access this field',
            $result['errors'][0]['message']
        );
    }

    public function testRegenerateSignatureKeyWithAnonymousToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling regenerateSignatureKey with anonymous token');

        $acceptanceTester->sendGQLQuery('query { token }');
        $token = $acceptanceTester->grabJsonResponseAsArray()['data']['token'];
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendRegenerateSignatureKeyMutation($acceptanceTester);

        $acceptanceTester->assertStringStartsWith(
            'You need to be logged to access this field',
            $result['errors'][0]['message']
        );
    }

    public function testRegenerateSignatureKeyWithUserToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling regenerateSignatureKey with normal user token');

        $token = $this->generateUserTokens($acceptanceTester, false);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendRegenerateSignatureKeyMutation($acceptanceTester);

        $acceptanceTester->assertStringStartsWith(
            'You do not have sufficient rights to access this field',
            $result['errors'][0]['message']
        );
    }

    public function testRegenerateSignatureKeyWithAdminToken(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->wantToTest('calling regenerateSignatureKey with special rights token');

        $token = $this->generateUserTokens($acceptanceTester);
        $acceptanceTester->amBearerAuthenticated($token);

        $result = $this->sendRegenerateSignatureKeyMutation($acceptanceTester);

        $acceptanceTester->assertTrue($result['data']['regenerateSignatureKey']);

        //fails on second call because the token is no longer valid for new signature
        $result = $this->sendRegenerateSignatureKeyMutation($acceptanceTester);

        $acceptanceTester->assertStringStartsWith('The access token is invalid', $result['errors'][0]['message']);
    }

    private function sendTokenQuery(AcceptanceTester $acceptanceTester, string $filterPart = ''): array
    {
        $query = ' query {
               tokens ' . $filterPart . ' {
                 id
                 customerId
                 expiresAt
                 shopId
              }
            }
        ';

        $acceptanceTester->sendGQLQuery($query);

        return $acceptanceTester->grabJsonResponseAsArray();
    }

    private function sendCustomerTokenDeleteMutation(AcceptanceTester $acceptanceTester, ?string $userId = null): array
    {
        $query = ' mutation {
               customerTokensDelete ';
        !$userId ?: $query .= '(customerId: "' . $userId . '")';
        $query .= '}';

        $acceptanceTester->sendGQLQuery($query);

        return $acceptanceTester->grabJsonResponseAsArray();
    }

    private function sendShopTokensDeleteMutation(AcceptanceTester $acceptanceTester): array
    {
        $query = ' mutation {
                       shopTokensDelete
                   }';

        $acceptanceTester->sendGQLQuery($query);

        return $acceptanceTester->grabJsonResponseAsArray();
    }

    private function sendRegenerateSignatureKeyMutation(AcceptanceTester $acceptanceTester): array
    {
        $query = ' mutation {
                       regenerateSignatureKey
                   }';

        $acceptanceTester->sendGQLQuery($query);

        return $acceptanceTester->grabJsonResponseAsArray();
    }

    private function adminDeletesAllUserTokens(AcceptanceTester $acceptanceTester, ?string $userId = null): array
    {
        $acceptanceTester->logout();

        $acceptanceTester->login(self::ADMIN_LOGIN, self::ADMIN_PASSWORD);

        if ($userId) {
            $this->sendTokenDeleteMutation($acceptanceTester, $userId);
        } else {
            $this->sendShopTokensDeleteMutation($acceptanceTester);
        }
        $acceptanceTester->logout();

        return $acceptanceTester->grabJsonResponseAsArray();
    }

    private function generateUserTokens(
        AcceptanceTester $acceptanceTester,
        bool $adminToken = true,
        bool $delay = false
    ): string {
        $acceptanceTester->logout();

        //four anonymous
        $this->generateToken($acceptanceTester);
        $this->generateToken($acceptanceTester);
        $this->generateToken($acceptanceTester);
        $this->generateToken($acceptanceTester);

        //two for admin
        $this->generateToken($acceptanceTester, self::ADMIN_LOGIN, self::ADMIN_PASSWORD);
        $token = $this->generateToken($acceptanceTester, self::ADMIN_LOGIN, self::ADMIN_PASSWORD);

        //three for demo user
        $this->generateToken($acceptanceTester, self::USER_LOGIN, $this->getUserPassword());
        $this->generateToken($acceptanceTester, self::USER_LOGIN, $this->getUserPassword());
        !$delay ?? $acceptanceTester->wait(1);
        $userToken = $this->generateToken($acceptanceTester, self::USER_LOGIN, $this->getUserPassword());
        $acceptanceTester->logout();

        return $adminToken ? $token : $userToken;
    }

    private function sendTokenDeleteMutation(AcceptanceTester $acceptanceTester, string $tokenId): array
    {
        $mutation = 'mutation ($tokenId: ID!) {
            tokenDelete(tokenId: $tokenId)
        }';

        $acceptanceTester->sendGQLQuery($mutation, ['tokenId' => $tokenId]);

        return $acceptanceTester->grabJsonResponseAsArray();
    }

    private function generateToken(AcceptanceTester $acceptanceTester, $username = null, $password = null): string
    {
        $query = 'query ($username: String, $password: String) {
            token (username: $username, password: $password)
        }';

        $acceptanceTester->sendGQLQuery($query, [
            'username' => $username,
            'password' => $password,
        ]);

        return $acceptanceTester->grabJsonResponseAsArray()['data']['token'];
    }

    private function getUserPassword(): string
    {
        return 'useruser';
    }
}
