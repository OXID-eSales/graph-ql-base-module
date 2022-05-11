<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit;

use OxidEsales\Eshop\Application\Model\User as UserModel;
use OxidEsales\GraphQL\Base\DataType\User as UserDataType;
use OxidEsales\GraphQL\Base\Infrastructure\Token as TokenInfrastructure;
use OxidEsales\GraphQL\Base\Service\JwtConfigurationBuilder;
use OxidEsales\GraphQL\Base\Service\ModuleConfiguration;
use OxidEsales\GraphQL\Base\Service\Token as TokenService;
use OxidEsales\GraphQL\Base\Service\TokenValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class BaseTestCase extends TestCase
{
    protected function getModuleConfigurationMock(string $lifetime = '+8 hours'): ModuleConfiguration
    {
        $moduleConfiguration = $this->getMockBuilder(ModuleConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $moduleConfiguration->method('getSignatureKey')
            ->willReturn('5wi3e0INwNhKe3kqvlH0m4FHYMo6hKef3SzweEjZ8EiPV7I2AC6ASZMpkCaVDTVRg2jbb52aUUXafxXI9/7Cgg==');

        $moduleConfiguration->method('getTokenLifeTime')
            ->willReturn($lifetime);

        $moduleConfiguration->method('getUserTokenQuota')
            ->willReturn(100);

        return $moduleConfiguration;
    }

    protected function getUserModelStub(?string $id = null): UserModel
    {
        $userModelStub = $this->createPartialMock(UserModel::class, ['getRawFieldData']);

        if ($id) {
            $userModelStub->setId($id);
        }

        return $userModelStub;
    }

    protected function getTokenValidator($legacy, $tokenInfrastructure = null): TokenValidator
    {
        return new TokenValidator(
            $this->getJwtConfigurationBuilder($legacy),
            $legacy,
            $tokenInfrastructure ?: $this->createPartialMock(
                TokenInfrastructure::class,
                ['registerToken', 'isTokenRegistered']
            )
        );
    }

    protected function getTokenService(
        $legacy,
        $tokenInfrastructure = null,
        $token = null,
        string $lifetime = '+8 hours'
    ): TokenService {
        return new TokenService(
            $token,
            $this->getJwtConfigurationBuilder($legacy),
            $legacy,
            $this->createPartialMock(EventDispatcherInterface::class, []),
            $this->getModuleConfigurationMock($lifetime),
            $tokenInfrastructure ?: $this->getTokenInfrastructureMock()
        );
    }

    protected function getTokenInfrastructureMock(): TokenInfrastructure
    {
        $mock = $this->createPartialMock(
            TokenInfrastructure::class,
            ['registerToken', 'isTokenRegistered', 'removeExpiredTokens', 'canIssueToken']
        );
        $mock->method('canIssueToken')->willReturn(true);

        return $mock;
    }

    protected function getJwtConfigurationBuilder($legacy = null): JwtConfigurationBuilder
    {
        return new JwtConfigurationBuilder(
            $this->getModuleConfigurationMock(),
            $legacy
        );
    }

    protected function getUserDataStub(?UserModel $model = null, bool $isAnonymous = false): UserDataType
    {
        return new UserDataType(
            $model ?: $this->createPartialMock(UserModel::class, ['getRawFieldData']),
            $isAnonymous
        );
    }
}
