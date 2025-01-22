<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Exception;

use OxidEsales\GraphQL\Base\Exception\ErrorCategories;
use OxidEsales\GraphQL\Base\Exception\TokenUserBlocked;
use PHPUnit\Framework\TestCase;

final class TokenUserBlockedTest extends TestCase
{
    public function testExceptionCategory(): void
    {
        $tokenUserBlocked = new TokenUserBlocked();

        $this->assertSame(ErrorCategories::PERMISSIONERRORS, $tokenUserBlocked->getCategory());
    }

    public function testIsClientSafe(): void
    {
        $tokenUserBlocked = new TokenUserBlocked();

        $this->assertTrue($tokenUserBlocked->isClientSafe());
    }

    public function testUserBlocked(): void
    {
        $tokenUserBlocked = new TokenUserBlocked();

        $this->assertSame('User is blocked', $tokenUserBlocked->getMessage());
    }
}
