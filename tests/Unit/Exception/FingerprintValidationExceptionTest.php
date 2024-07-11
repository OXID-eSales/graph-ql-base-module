<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Exception;

use OxidEsales\GraphQL\Base\Exception\Error;
use OxidEsales\GraphQL\Base\Exception\ErrorCategories;
use OxidEsales\GraphQL\Base\Exception\FingerprintValidationException;
use PHPUnit\Framework\TestCase;

class FingerprintValidationExceptionTest extends TestCase
{
    public function testExceptionCategory(): void
    {
        $fingerprintMissingException = new FingerprintValidationException(uniqid());

        $this->assertSame(ErrorCategories::REQUESTERROR, $fingerprintMissingException->getCategory());
    }

    public function testExceptionType(): void
    {
        $fingerprintMissingException = new FingerprintValidationException(uniqid());

        $this->assertInstanceOf(Error::class, $fingerprintMissingException);
    }
}
