<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Integration\Infrastructure;

use DateTime;
use DateTimeImmutable;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionFactoryInterface;
use OxidEsales\GraphQL\Base\DataType\UserInterface;
use OxidEsales\GraphQL\Base\Exception\InvalidRefreshToken;
use OxidEsales\GraphQL\Base\Infrastructure\RefreshTokenRepository;
use OxidEsales\GraphQL\Base\Infrastructure\RefreshTokenRepositoryInterface;
use OxidEsales\GraphQL\Base\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RefreshTokenRepository::class)]
class RefreshTokenRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->cleanUp();

        parent::tearDown();
    }

    public function testGetNewRefreshTokenGivesCorrectlyFilledDataType(): void
    {
        $sut = $this->getSut();

        $userId = uniqid();
        $token = $sut->getNewRefreshToken(
            userId: $userId,
            lifeTime: $lifetime = '+1 month',
        );
        $id = $token->id()->val();

        $this->assertNotEmpty($id);
        $this->assertEquals(1, $token->shopId()->val());
        $this->assertSame($userId, $token->customerId()->val());
        $this->assertTrue(strlen($token->token()) === 255);

        $this->assertSame(
            (new DateTime('now'))->format("Y-m-d H:i"),
            $token->createdAt()->format("Y-m-d H:i")
        );

        $this->assertSame(
            (new DateTime($lifetime))->format("Y-m-d H:i"),
            $token->expiresAt()->format("Y-m-d H:i")
        );
    }

    public function testGetNewRefreshTokenRegistersTokenInDatabase(): void
    {
        $sut = $this->getSut();

        $token = $sut->getNewRefreshToken(
            userId: uniqid(),
            lifeTime: '+1 month',
        );
        $id = $token->id()->val();

        $this->assertTrue($this->checkRefreshTokenWithIdExists($id));
    }

    public function testRemoveExpiredTokens(): void
    {
        $this->addToken(
            oxid: $expiredId = uniqid(),
            expires: (new DateTime('-1 day'))->format(DateTime::ATOM)
        );

        $this->addToken(
            oxid: $notExpiredId = uniqid(),
            expires: (new DateTime('+1 day'))->format(DateTime::ATOM)
        );

        $sut = $this->getSut();
        $sut->removeExpiredTokens();

        $this->assertFalse($this->checkRefreshTokenWithIdExists($expiredId));
        $this->assertTrue($this->checkRefreshTokenWithIdExists($notExpiredId));
    }

    public function testGetTokenUserReturnsExistingUserBySpecificToken(): void
    {
        $this->addToken(
            oxid: uniqid(),
            expires: (new DateTime('+1 day'))->format(DateTime::ATOM),
            userId: $userId = 'oxdefaultadmin',
            token: $token = uniqid(),
        );

        $sut = $this->getSut();

        $user = $sut->getTokenUser($token);

        $this->assertFalse($user->isAnonymous());
        $this->assertSame($user->id()->val(), $userId);
    }

    public function testGetTokenUserReturnsAnonymousUserBySpecificToken(): void
    {
        $this->addToken(
            oxid: uniqid(),
            expires: (new DateTime('+1 day'))->format(DateTime::ATOM),
            userId: $userId = uniqid(),
            token: $token = uniqid(),
        );

        $sut = $this->getSut();

        $user = $sut->getTokenUser($token);

        $this->assertTrue($user->isAnonymous());
        $this->assertSame($user->id()->val(), $userId);
    }

    public function testGetTokenUserExplodesOnExpiredToken(): void
    {
        $this->addToken(
            oxid: uniqid(),
            expires: (new DateTime('-1 day'))->format(DateTime::ATOM),
            userId: 'oxdefaultadmin',
            token: $token = uniqid(),
        );

        $sut = $this->getSut();

        $this->expectException(InvalidRefreshToken::class);
        $sut->getTokenUser($token);
    }

    public function testGetTokenUserExplodesOnWrongToken(): void
    {
        $sut = $this->getSut();

        $this->expectException(InvalidRefreshToken::class);
        $sut->getTokenUser(uniqid());
    }

    public function testInvalidateRefreshTokens(): void
    {
        $expires = new DateTimeImmutable('+8 hours');
        $this->addToken(
            oxid: 'pwd_change_token',
            expires: $expires->format('Y-m-d H:i:s'),
            userId: $userId = '_testUser',
            token: $token = uniqid(),
        );

        $sut = $this->getSut();
        $sut->invalidateUserTokens($userId);

        $this->expectException(InvalidRefreshToken::class);
        $sut->getTokenUser($token);
    }

    public function testInvalidateRefreshTokensWrongUserId(): void
    {
        $expires = new DateTimeImmutable('+8 hours');
        $this->addToken(
            oxid: 'pwd_change_token',
            expires: $expires->format('Y-m-d H:i:s'),
            userId: '_testUser',
            token: $token = uniqid(),
        );

        $sut = $this->getSut();
        $sut->invalidateUserTokens('some_user_id');

        $this->assertTrue($sut->getTokenUser($token) instanceof UserInterface);
    }

    public function getSut(): RefreshTokenRepositoryInterface
    {
        return self::$container->get(RefreshTokenRepositoryInterface::class);
    }

    private function checkRefreshTokenWithIdExists(string $oxid): bool
    {
        $result = ContainerFacade::get(ConnectionFactoryInterface::class)
            ->create()
            ->executeQuery(
                "select count(*) from `oegraphqlrefreshtoken` where OXID=:oxid",
                ['oxid' => $oxid]
        );

        return $result->fetchOne() > 0;
    }

    public function addToken(
        string $oxid,
        string $expires,
        string $userId = null,
        string $token = null,
    ): void {
        $insertTokensQuery = "insert into `oegraphqlrefreshtoken` (OXID, OXUSERID, TOKEN, EXPIRES_AT)
            values (:oxid, :oxuserid, :token, :expires)";
        ContainerFacade::get(ConnectionFactoryInterface::class)
            ->create()
            ->executeQuery($insertTokensQuery, [
            "oxid" => $oxid,
            "oxuserid" => $userId ?? uniqid(),
            'token' => $token ?? uniqid(),
            "expires" => $expires,
        ]);
    }

    private function cleanUp(): void
    {
        ContainerFacade::get(ConnectionFactoryInterface::class)
            ->create()
            ->executeQuery(
                'truncate table `oegraphqlrefreshtoken`'
            );
    }
}
