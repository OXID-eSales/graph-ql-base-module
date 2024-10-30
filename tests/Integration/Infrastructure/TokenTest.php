<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Integration\Infrastructure;

use DateTimeImmutable;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\UnencryptedToken;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\EshopCommunity\Tests\TestContainerFactory;
use OxidEsales\GraphQL\Base\DataType\Token as TokenDataType;
use OxidEsales\GraphQL\Base\DataType\User as UserDataType;
use OxidEsales\GraphQL\Base\Infrastructure\Model\Token as TokenModel;
use OxidEsales\GraphQL\Base\Infrastructure\Token as TokenInfrastructure;
use OxidEsales\GraphQL\Base\Service\Token;
use OxidEsales\GraphQL\Base\Service\Token as TokenService;

class TokenTest extends IntegrationTestCase
{
    private const TEST_TOKEN_ID = '_my_test_token';

    private const TEST_USER_ID = '_testuser';

    /** @var TokenInfrastructure */
    private $tokenInfrastructure;

    public function setUp(): void
    {
        parent::setUp();
        $containerFactory = new TestContainerFactory();
        $container = $containerFactory->create();
        $container->compile();
        $this->tokenInfrastructure = $container->get(TokenInfrastructure::class);
    }

    public function testRegisterToken(): void
    {
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock(),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );

        $tokenModel = oxNew(TokenModel::class);
        $tokenModel->load(self::TEST_TOKEN_ID);

        $this->assertTrue($tokenModel->isLoaded());
    }

    public function testIsTokenRegistered(): void
    {
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock(),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );

        $this->assertTrue($this->tokenInfrastructure->isTokenRegistered(self::TEST_TOKEN_ID));
    }

    public function testIsTokenRegisteredNo(): void
    {
        $this->assertFalse($this->tokenInfrastructure->isTokenRegistered('not_registered_token'));
    }

    public function testIsTokenExpired(): void
    {
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('valid_token'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );

        $this->assertFalse($this->tokenInfrastructure->isTokenExpired('valid_token'));
    }

    public function testIsTokenExpiredNo(): void
    {
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('expired_token'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('-8 hours')
        );

        $this->assertTrue($this->tokenInfrastructure->isTokenExpired('expired_token'));
    }

    public function testRemoveExpiredTokens(): void
    {
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_first'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('-8 hours')
        );
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_second'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('-8 hours')
        );
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_third'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_other', '_otheruser'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('-8 hours')
        );

        $userModel = oxNew(User::class);
        $userModel->setId(self::TEST_USER_ID);
        $user = new UserDataType($userModel);

        $this->tokenInfrastructure->removeExpiredTokens($user);

        $this->assertFalse($this->tokenInfrastructure->isTokenRegistered('_first'));
        $this->assertFalse($this->tokenInfrastructure->isTokenRegistered('_second'));
        $this->assertTrue($this->tokenInfrastructure->isTokenRegistered('_third'));
        $this->assertTrue($this->tokenInfrastructure->isTokenRegistered('_other'));
    }

    public function testCanIssueToken(): void
    {
        $userModel = oxNew(User::class);
        $userModel->setId(self::TEST_USER_ID);
        $user = new UserDataType($userModel);

        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_first'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_second'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_third'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );

        $this->assertTrue($this->tokenInfrastructure->canIssueToken($user, 5));
        $this->assertTrue(
            $this->tokenInfrastructure->canIssueToken($user, 4)
        );  //three are stored, quota is 4, we can issue another token
        $this->assertFalse(
            $this->tokenInfrastructure->canIssueToken($user, 3)
        ); //three are stored, quota is 3, we cannot issue another token right now
        $this->assertFalse($this->tokenInfrastructure->canIssueToken($user, 2));
    }

    public function testTokenDelete(): void
    {
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_first'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_second'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_third'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_other', '_otheruser'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_else', '_elseuser'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );

        $userModel = oxNew(User::class);
        $userModel->setId(self::TEST_USER_ID);
        $user = new UserDataType($userModel);

        $this->tokenInfrastructure->tokenDelete($user, '_first');
        $this->assertFalse($this->tokenInfrastructure->isTokenRegistered('_first'));
        $this->assertTrue($this->tokenInfrastructure->isTokenRegistered('_second'));
        $this->assertTrue($this->tokenInfrastructure->isTokenRegistered('_third'));
        $this->assertTrue($this->tokenInfrastructure->isTokenRegistered('_other'));
        $this->assertTrue($this->tokenInfrastructure->isTokenRegistered('_else'));

        $this->tokenInfrastructure->tokenDelete($user);
        $this->assertFalse($this->tokenInfrastructure->isTokenRegistered('_second'));
        $this->assertFalse($this->tokenInfrastructure->isTokenRegistered('_third'));
        $this->assertTrue($this->tokenInfrastructure->isTokenRegistered('_other'));
        $this->assertTrue($this->tokenInfrastructure->isTokenRegistered('_else'));

        $this->tokenInfrastructure->tokenDelete(null, '_other');
        $this->assertFalse($this->tokenInfrastructure->isTokenRegistered('_other'));
        $this->assertTrue($this->tokenInfrastructure->isTokenRegistered('_else'));

        $this->tokenInfrastructure->tokenDelete(null, null, 1);
        $this->assertFalse($this->tokenInfrastructure->isTokenRegistered('_else'));
    }

    public function testTokenDeleteAll(): void
    {
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_first'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_second'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_third'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_other', '_otheruser'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_else', '_elseuser'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );

        $this->tokenInfrastructure->tokenDelete();
        $this->assertFalse($this->tokenInfrastructure->isTokenRegistered('_first'));
        $this->assertFalse($this->tokenInfrastructure->isTokenRegistered('_second'));
        $this->assertFalse($this->tokenInfrastructure->isTokenRegistered('_third'));
        $this->assertFalse($this->tokenInfrastructure->isTokenRegistered('_other'));
        $this->assertFalse($this->tokenInfrastructure->isTokenRegistered('_else'));
    }

    public function testTokenDataType(): void
    {
        $tokenModel = oxNew(TokenModel::class);

        $tokenModel->setId('_testId');
        $tokenModel->assign(
            [
                'OXID' => '_tokenId',
                'OXSHOPID' => '66',
                'OXUSERID' => '_userId',
                'ISSUED_AT' => '2021-12-01 12:12:12',
                'EXPIRES_AT' => '2021-12-02 12:12:12',
                'USERAGENT' => 'the user agent',
                'TOKEN' => 'very_large_string',
            ]
        );
        $tokenModel->save();
        $tokenModel->load('_testId');

        $tokenDataType = new TokenDataType($tokenModel);

        $this->assertSame($tokenModel, $tokenDataType->getEshopModel());
        $this->assertSame(TokenModel::class, TokenDataType::getModelClass());
        $this->assertSame($tokenModel->getRawFieldData('oxid'), $tokenDataType->id()->val());
        $this->assertSame($tokenModel->getRawFieldData('token'), $tokenDataType->token());
        $this->assertSame(
            $tokenModel->getRawFieldData('issued_at'),
            $tokenDataType->createdAt()->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            $tokenModel->getRawFieldData('expires_at'),
            $tokenDataType->expiresAt()->format('Y-m-d H:i:s')
        );
        $this->assertSame($tokenModel->getRawFieldData('useragent'), $tokenDataType->userAgent());
        $this->assertSame($tokenModel->getRawFieldData('oxshopid'), $tokenDataType->shopId()->val());
        $this->assertSame($tokenModel->getRawFieldData('oxuserid'), $tokenDataType->customerId()->val());
    }

    public function testUserHasToken(): void
    {
        $userModel = oxNew(User::class);
        $userModel->setId(self::TEST_USER_ID);
        $user = new UserDataType($userModel);

        $otherUserModel = oxNew(User::class);
        $otherUserModel->setId('_other_id');
        $otherUser = new UserDataType($otherUserModel);

        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_first'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );

        $this->assertTrue($this->tokenInfrastructure->userHasToken($user, '_first'));
        $this->assertFalse($this->tokenInfrastructure->userHasToken($user, '_second'));
        $this->assertFalse($this->tokenInfrastructure->userHasToken($otherUser, '_first'));
        $this->assertFalse($this->tokenInfrastructure->userHasToken($otherUser, '_second'));
    }

    public function testInvalidateTokenAfterDeleteUser(): void
    {
        $userModel = oxNew(User::class);
        $userModel->setId('_testUser');
        $userModel->setPassword('_testPassword');
        $userModel->assign(['oxusername' => '_testUsername']);
        $userModel->save();

        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock('_deletedUser'),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );

        $user = new UserDataType($userModel);
        $this->assertTrue($this->tokenInfrastructure->userHasToken($user, '_deletedUser'));

        $userModel->delete(self::TEST_USER_ID);
        $this->assertFalse($this->tokenInfrastructure->isTokenRegistered('_deletedUser'));
    }

    public function testInvalidateAccessTokens(): void
    {
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock(
                $token = 'pwd_change_token',
                $userId = '_testUser'
            ),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );

        $this->tokenInfrastructure->invalidateUserTokens($userId);
        $this->assertTrue($this->tokenInfrastructure->isTokenExpired($token));
    }

    public function testInvalidateAccessTokensWrongUserId(): void
    {
        $this->tokenInfrastructure->registerToken(
            $this->getTokenMock(
                tokenId: $token = 'pwd_change_token',
                userId: '_testUser'
            ),
            new DateTimeImmutable('now'),
            new DateTimeImmutable('+8 hours')
        );

        $this->tokenInfrastructure->invalidateUserTokens('wrong_user_id');
        $this->assertFalse($this->tokenInfrastructure->isTokenExpired($token));
    }

    private function getTokenMock(
        string $tokenId = self::TEST_TOKEN_ID,
        string $userId = self::TEST_USER_ID
    ): UnencryptedToken {
        $claims = new DataSet(
            [
                TokenService::CLAIM_TOKENID => $tokenId,
                TokenService::CLAIM_SHOPID => 1,
                TokenService::CLAIM_USERID => $userId,
            ],
            ''
        );

        $token = $this->getMockBuilder(UnencryptedToken::class)
            ->getMock();
        $token->method('claims')->willReturn($claims);
        $token->method('toString')->willReturn('here_is_the_string_token');

        return $token;
    }
}
