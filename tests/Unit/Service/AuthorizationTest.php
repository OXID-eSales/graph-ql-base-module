<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Service;

use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\UnencryptedToken;
use OxidEsales\GraphQL\Base\Event\BeforeAuthorization;
use OxidEsales\GraphQL\Base\Framework\PermissionProviderInterface;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\GraphQL\Base\Infrastructure\Token as TokenInfrastructure;
use OxidEsales\GraphQL\Base\Service\Authorization;
use OxidEsales\GraphQL\Base\Service\JwtConfigurationBuilder;
use OxidEsales\GraphQL\Base\Service\Token as TokenService;
use OxidEsales\GraphQL\Base\Tests\Unit\BaseTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AuthorizationTest extends BaseTestCase
{
    public function testIsNotAllowedWithoutPermissionsAndWithoutToken(): void
    {
        $auth = new Authorization(
            [],
            $this->getEventDispatcherMock(),
            $this->prepareTokenService(),
            $this->getLegacyMock()
        );

        $this->assertFalse($auth->isAllowed(''));
    }

    public function testIsNotAllowedWithoutPermissionsButWithToken(): void
    {
        $auth = new Authorization(
            [],
            $this->getEventDispatcherMock(),
            $this->prepareTokenService($this->getTokenMock(), $this->getUserGroupsMock()),
            $this->getUserGroupsMock()
        );

        $this->assertFalse(
            $auth->isAllowed('foo')
        );
    }

    public function testIsNotAllowedWithPermissionsButWithoutToken(): void
    {
        $auth = new Authorization(
            $this->getPermissionMocks(),
            $this->getEventDispatcherMock(),
            $this->prepareTokenService(null, $this->getLegacyMock()),
            $this->getLegacyMock()
        );

        $this->assertFalse(
            $auth->isAllowed('permission')
        );
    }

    public function testIsAllowedWithPermissionsAndWithToken(): void
    {
        $auth = new Authorization(
            $this->getPermissionMocks(),
            $this->getEventDispatcherMock(),
            $this->prepareTokenService($this->getTokenMock(), $this->getUserGroupsMock()),
            $this->getUserGroupsMock()
        );

        $this->assertTrue(
            $auth->isAllowed('permission'),
            'Permission "permission" must be granted to group "group"'
        );
        $this->assertTrue(
            $auth->isAllowed('permission2'),
            'Permission "permission2" must be granted to group "group"'
        );
        $this->assertFalse(
            $auth->isAllowed('permission1'),
            'Permission "permission1" must not be granted to group "group"'
        );
    }

    public function testPositiveOverrideAuthBasedOnEvent(): void
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            BeforeAuthorization::class,
            function (BeforeAuthorization $event): void {
                $event->setAuthorized(true);
            }
        );

        $auth = new Authorization(
            $this->getPermissionMocks(),
            $eventDispatcher,
            $this->prepareTokenService($this->getTokenMock(), $this->getUserGroupsMock()),
            $this->getUserGroupsMock()
        );

        $this->assertTrue(
            $auth->isAllowed('permission'),
            'Permission "permission" must be granted to group "group"'
        );
        $this->assertTrue(
            $auth->isAllowed('permission2'),
            'Permission "permission2" must be granted to group "group"'
        );
        $this->assertTrue(
            $auth->isAllowed('permission1'),
            'Permission "permission1" must be granted to group "group"'
        );
    }

    public function testNegativeOverrideAuthBasedOnEvent(): void
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            BeforeAuthorization::class,
            function (BeforeAuthorization $event): void {
                $event->setAuthorized(false);
            }
        );
        $auth = new Authorization(
            $this->getPermissionMocks(),
            $eventDispatcher,
            $this->prepareTokenService($this->getTokenMock(), $this->getUserGroupsMock()),
            $this->getUserGroupsMock()
        );

        $this->assertFalse(
            $auth->isAllowed('permission'),
            'Permission "permission" must not be granted to group "group"'
        );
        $this->assertFalse(
            $auth->isAllowed('permission2'),
            'Permission "permission2" must not be granted to group "group"'
        );
        $this->assertFalse(
            $auth->isAllowed('permission1'),
            'Permission "permission1" must not be granted to group "group"'
        );
    }

    protected function prepareTokenService(
        ?UnencryptedToken $token = null,
        ?Legacy $legacyService = null,
        ?TokenInfrastructure $tokenInfrastructure = null
    ): TokenService {
        return new class (
            $token,
            $this->createPartialMock(JwtConfigurationBuilder::class, []),
            $legacyService ?: $this->getLegacyMock(),
            $this->createPartialMock(EventDispatcher::class, []),
            $this->getModuleConfigurationMock(),
            $tokenInfrastructure ?: $this->getTokenInfrastructureMock(),
            $this->getRefreshRepositoryMock()
        ) extends TokenService {
            protected function areConstraintsValid(UnencryptedToken $token): bool
            {
                return true;
            }
        };
    }

    private function getTokenMock(): UnencryptedToken
    {
        $claims = new DataSet(
            [
                TokenService::CLAIM_USERNAME => 'testuser',
            ],
            ''
        );

        $token = $this->getMockBuilder(UnencryptedToken::class)->getMock();
        $token->method('claims')->willReturn($claims);

        return $token;
    }

    private function getPermissionMocks(): iterable
    {
        $a = $this->getMockBuilder(PermissionProviderInterface::class)
            ->getMock();
        $a->method('getPermissions')
            ->willReturn([
                'group' => ['permission'],
                'group1' => ['permission1'],
            ]);
        $b = $this->getMockBuilder(PermissionProviderInterface::class)
            ->getMock();
        $b->method('getPermissions')
            ->willReturn([
                'group' => ['permission2'],
                'group2' => ['permission2'],
                'developer' => ['all'],
            ]);

        return [
            $a,
            $b,
        ];
    }

    private function getEventDispatcherMock(): EventDispatcherInterface
    {
        return $this->getMockBuilder(EventDispatcherInterface::class)
            ->getMock();
    }

    private function getLegacyMock(): Legacy
    {
        return $this->getMockBuilder(Legacy::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getUserGroupsMock(): Legacy
    {
        $legacyMock = $this->getLegacyMock();
        $legacyMock
            ->method('getUserGroupIds')
            ->willReturn(['group']);

        return $legacyMock;
    }
}
