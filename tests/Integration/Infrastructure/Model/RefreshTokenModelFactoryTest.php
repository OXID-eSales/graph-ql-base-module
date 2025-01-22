<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Integration\Infrastructure\Model;

use OxidEsales\GraphQL\Base\Infrastructure\Model\RefreshToken;
use OxidEsales\GraphQL\Base\Infrastructure\Model\RefreshTokenModelFactory;
use OxidEsales\GraphQL\Base\Infrastructure\Model\RefreshTokenModelFactoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RefreshTokenModelFactory::class)]
class RefreshTokenModelFactoryTest extends TestCase
{
    public function testCreateProducesCorrectTypeOfObjects(): void
    {
        $refreshTokenModelFactory = $this->getSut();

        $this->assertInstanceOf(RefreshToken::class, $refreshTokenModelFactory->create());
    }

    public function testCreateProducesDifferentObjectsOnEveryCall(): void
    {
        $refreshTokenModelFactory = $this->getSut();

        $refreshToken = $refreshTokenModelFactory->create();
        $model2 = $refreshTokenModelFactory->create();

        $this->assertNotSame($refreshToken, $model2);
    }

    private function getSut(): RefreshTokenModelFactoryInterface
    {
        return new RefreshTokenModelFactory();
    }
}
