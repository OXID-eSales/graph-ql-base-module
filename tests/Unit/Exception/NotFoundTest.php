<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Exception;

use OxidEsales\GraphQL\Base\Exception\ErrorCategories;
use OxidEsales\GraphQL\Base\Exception\NotFound;
use PHPUnit\Framework\TestCase;

final class NotFoundTest extends TestCase
{
    public function testExceptionCategory(): void
    {
        $notFound = new NotFound();

        $this->assertSame(ErrorCategories::REQUESTERROR, $notFound->getCategory());
    }

    public function testIsClientSafe(): void
    {
        $notFound = new NotFound();

        $this->assertTrue($notFound->isClientSafe());
    }

    public function testNotFound(): void
    {
        $notFound = new NotFound();

        $this->assertSame('Queried data was not found', $notFound->getMessage());
    }
}
