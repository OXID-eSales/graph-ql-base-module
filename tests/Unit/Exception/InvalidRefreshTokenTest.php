<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Exception;

use OxidEsales\GraphQL\Base\Exception\ErrorCategories;
use OxidEsales\GraphQL\Base\Exception\InvalidRefreshToken;
use PHPUnit\Framework\TestCase;

final class InvalidRefreshTokenTest extends TestCase
{
    public function testExceptionCategory(): void
    {
        $invalidRefreshToken = new InvalidRefreshToken();

        $this->assertSame(ErrorCategories::PERMISSIONERRORS, $invalidRefreshToken->getCategory());
    }

    public function testIsClientSafe(): void
    {
        $invalidRefreshToken = new InvalidRefreshToken();

        $this->assertTrue($invalidRefreshToken->isClientSafe());
    }

    public function testInvalidToken(): void
    {
        $invalidRefreshToken = new InvalidRefreshToken();

        $this->assertSame('The refresh token is invalid', $invalidRefreshToken->getMessage());
    }
}
