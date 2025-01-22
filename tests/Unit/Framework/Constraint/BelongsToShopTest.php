<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Framework;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\ConstraintViolation;
use OxidEsales\GraphQL\Base\Framework\Constraint\BelongsToShop;
use PHPUnit\Framework\TestCase;

class BelongsToShopTest extends TestCase
{
    public function testTypeException(): void
    {
        $this->expectException(ConstraintViolation::class);
        $tokenStub = $this->createPartialMock(Token::class, []);
        $belongsToShop = new BelongsToShop(1);
        $belongsToShop->assert($tokenStub);
    }
}
