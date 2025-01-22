<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Exception;

use OxidEsales\GraphQL\Base\Exception\ErrorCategories;
use OxidEsales\GraphQL\Base\Exception\UnableToParseToken;
use PHPUnit\Framework\TestCase;

final class UnableToParseTokenTest extends TestCase
{
    public function testExceptionCategory(): void
    {
        $unableToParseToken = new UnableToParseToken();

        $this->assertSame(ErrorCategories::PERMISSIONERRORS, $unableToParseToken->getCategory());
    }

    public function testIsClientSafe(): void
    {
        $unableToParseToken = new UnableToParseToken();

        $this->assertTrue($unableToParseToken->isClientSafe());
    }

    public function testUnableToParse(): void
    {
        $unableToParseToken = new UnableToParseToken();

        $this->assertSame('Unable to parse token', $unableToParseToken->getMessage());
    }
}
