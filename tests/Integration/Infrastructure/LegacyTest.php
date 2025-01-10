<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Integration\Infrastructure;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Tests\TestContainerFactory;
use OxidEsales\GraphQL\Base\DataType\User as UserDataType;
use OxidEsales\GraphQL\Base\Exception\InvalidLogin;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\GraphQL\Base\Tests\Integration\TestCase;

class LegacyTest extends TestCase
{
    private const ADMIN_LOGIN = 'noreply@oxid-esales.com';

    private const ADMIN_PASSWORD = 'admin';

    /** @var Legacy */
    private $legacyInfrastructure;

    public function setUp(): void
    {
        parent::setUp();
        $containerFactory = new TestContainerFactory();
        $container = $containerFactory->create();
        $container->compile();
        $this->legacyInfrastructure = $container->get(Legacy::class);
    }

    public function testValidLogin(): void
    {
        $user = $this->legacyInfrastructure->login(self::ADMIN_LOGIN, self::ADMIN_PASSWORD);
        $this->assertSame($user::class, UserDataType::class);
    }

    public function testInvalidLogin(): void
    {
        $this->expectException(InvalidLogin::class);
        $this->legacyInfrastructure->login(
            self::ADMIN_LOGIN,
            'wrongpassword'
        );
    }

    /**
     * @dataProvider loginResponseTestDataProvider
     */
    public function testLoginResponseIsAnonymousOnLoginMissing(
        ?string $login,
        ?string $password,
        ?bool $expectedAnonymous,
        ?bool $expectedUserIdNull,
        bool $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException(InvalidLogin::class);
        }

        $userDataType = $this->legacyInfrastructure->login($login, $password);

        $this->assertSame($expectedAnonymous, $userDataType->isAnonymous());
        $this->assertSame($expectedUserIdNull, null === $userDataType->id());
    }

    public static function loginResponseTestDataProvider(): array
    {
        return [
            'no login' => [
                'login' => null,
                'password' => 'any',
                'expectedAnonymous' => true,
                'expectedUserIdNull' => false,
                'expectedException' => false,
            ],
            'no password' => [
                'login' => 'any',
                'password' => null,
                'expectedAnonymous' => true,
                'expectedUserIdNull' => false,
                'expectedException' => false,
            ],
            'no values' => [
                'login' => null,
                'password' => null,
                'expectedAnonymous' => true,
                'expectedUserIdNull' => false,
                'expectedException' => false,
            ],
            'wrong login' => [
                'login' => 'xxx',
                'password' => 'yyy',
                'expectedAnonymous' => null,
                'expectedUserIdNull' => null,
                'expectedException' => true,
            ],
            'correct login' => [
                'login' => self::ADMIN_LOGIN,
                'password' => self::ADMIN_PASSWORD,
                'expectedAnonymous' => false,
                'expectedUserIdNull' => false,
                'expectedException' => false,
            ],
        ];
    }

    public function testUserGroups(): void
    {
        $user = oxNew(User::class);

        $noUserGroups = $this->legacyInfrastructure->getUserGroupIds($user->getId());
        $this->assertSame([], $noUserGroups);

        $user->setId('_testUser');
        $user->setPassword('_testPassword');
        $user->assign(['oxusername' => '_testUsername']);

        $anonymousUserGroup = $this->legacyInfrastructure->getUserGroupIds($user->getId());
        $this->assertSame(['oxidanonymous'], $anonymousUserGroup);

        $groups = ['_testGroup', '_tempGroup'];
        $this->addToGroupsToUser($user, $groups);

        $withUserGroups = $this->legacyInfrastructure->getUserGroupIds($user->getId());
        $this->assertCount(2, $withUserGroups);
        $this->assertEmpty(array_diff($groups, array_values($withUserGroups)));

        $otherGroups = ['_newGroup', '_hiddenGroup', '_wrongGroup'];
        $this->addToGroupsToUser($user, $otherGroups);

        $allGroups = array_merge($groups, $otherGroups);
        $allUserGroups = $this->legacyInfrastructure->getUserGroupIds($user->getId());
        $this->assertCount(5, $allUserGroups);
        $this->assertEmpty(array_diff($groups, array_values($allGroups)));
    }

    /**
     * @param string[] $groupIds
     */
    private function addToGroupsToUser(User $user, array $groupIds = []): void
    {
        foreach ($groupIds as $groupId) {
            $group = oxNew('oxGroups');
            $group->setId($groupId);
            $group->assign([
                'oxtitle' => $groupId,
                'oxactive' => 1,
            ]);
            $group->save();

            $user->addToGroup($groupId);
        }

        $user->save();
    }
}
