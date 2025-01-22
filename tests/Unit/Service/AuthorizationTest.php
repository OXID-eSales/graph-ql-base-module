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
        $authorization = new Authorization(
            [],
            $this->getEventDispatcherMock(),
            $this->prepareTokenService(),
            $this->getLegacyMock()
        );

        $this->assertFalse($authorization->isAllowed(''));
    }

    public function testIsNotAllowedWithoutPermissionsButWithToken(): void
    {
        $authorization = new Authorization(
            [],
            $this->getEventDispatcherMock(),
            $this->prepareTokenService($this->getTokenMock(), $this->getUserGroupsMock()),
            $this->getUserGroupsMock()
        );

        $this->assertFalse(
            $authorization->isAllowed('foo')
        );
    }

    public function testIsNotAllowedWithPermissionsButWithoutToken(): void
    {
        $authorization = new Authorization(
            $this->getPermissionMocks(),
            $this->getEventDispatcherMock(),
            $this->prepareTokenService(null, $this->getLegacyMock()),
            $this->getLegacyMock()
        );

        $this->assertFalse(
            $authorization->isAllowed('permission')
        );
    }

    public function testIsAllowedWithPermissionsAndWithToken(): void
    {
        $authorization = new Authorization(
            $this->getPermissionMocks(),
            $this->getEventDispatcherMock(),
            $this->prepareTokenService($this->getTokenMock(), $this->getUserGroupsMock()),
            $this->getUserGroupsMock()
        );

        $this->assertTrue(
            $authorization->isAllowed('permission'),
            'Permission "permission" must be granted to group "group"'
        );
        $this->assertTrue(
            $authorization->isAllowed('permission2'),
            'Permission "permission2" must be granted to group "group"'
        );
        $this->assertFalse(
            $authorization->isAllowed('permission1'),
            'Permission "permission1" must not be granted to group "group"'
        );
    }

    public function testPositiveOverrideAuthBasedOnEvent(): void
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            BeforeAuthorization::class,
            function (BeforeAuthorization $beforeAuthorization): void {
                $beforeAuthorization->setAuthorized(true);
            }
        );

        $authorization = new Authorization(
            $this->getPermissionMocks(),
            $eventDispatcher,
            $this->prepareTokenService($this->getTokenMock(), $this->getUserGroupsMock()),
            $this->getUserGroupsMock()
        );

        $this->assertTrue(
            $authorization->isAllowed('permission'),
            'Permission "permission" must be granted to group "group"'
        );
        $this->assertTrue(
            $authorization->isAllowed('permission2'),
            'Permission "permission2" must be granted to group "group"'
        );
        $this->assertTrue(
            $authorization->isAllowed('permission1'),
            'Permission "permission1" must be granted to group "group"'
        );
    }

    public function testNegativeOverrideAuthBasedOnEvent(): void
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            BeforeAuthorization::class,
            function (BeforeAuthorization $beforeAuthorization): void {
                $beforeAuthorization->setAuthorized(false);
            }
        );
        $authorization = new Authorization(
            $this->getPermissionMocks(),
            $eventDispatcher,
            $this->prepareTokenService($this->getTokenMock(), $this->getUserGroupsMock()),
            $this->getUserGroupsMock()
        );

        $this->assertFalse(
            $authorization->isAllowed('permission'),
            'Permission "permission" must not be granted to group "group"'
        );
        $this->assertFalse(
            $authorization->isAllowed('permission2'),
            'Permission "permission2" must not be granted to group "group"'
        );
        $this->assertFalse(
            $authorization->isAllowed('permission1'),
            'Permission "permission1" must not be granted to group "group"'
        );
    }

    protected function prepareTokenService(
        ?UnencryptedToken $unencryptedToken = null,
        ?Legacy $legacy = null,
        ?TokenInfrastructure $tokenInfrastructure = null
    ): TokenService {
        return new class (
            $token,
            $this->createPartialMock(JwtConfigurationBuilder::class, []),
            $legacy ?: $this->getLegacyMock(),
            $this->createPartialMock(EventDispatcher::class, []),
            $this->getModuleConfigurationMock(),
            $tokenInfrastructure ?: $this->getTokenInfrastructureMock(),
            $this->getRefreshRepositoryMock()
        ) extends TokenService {
            protected function areConstraintsValid(UnencryptedToken $unencryptedToken): bool
            {
                return true;
            }
        };
    }

    private function getTokenMock(): UnencryptedToken
    {
        $dataSet = new DataSet(
            [
                TokenService::CLAIM_USERNAME => 'testuser',
            ],
            ''
        );

        $token = $this->getMockBuilder(UnencryptedToken::class)->getMock();
        $token->method('claims')->willReturn($dataSet);

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
