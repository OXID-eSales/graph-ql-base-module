<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Event;

use Lcobucci\JWT\Token;
use OxidEsales\GraphQL\Base\Event\BeforeAuthorization;
use PHPUnit\Framework\TestCase;

class BeforeAuthorizationTest extends TestCase
{
    public function testBasicGetters(): void
    {
        $tokenStub = $this->createPartialMock(Token::class, []);

        $beforeAuthorization = new BeforeAuthorization(
            $tokenStub,
            'right'
        );

        $this->assertInstanceOf(
            Token::class,
            $beforeAuthorization->getToken()
        );
        $this->assertSame(
            'right',
            $beforeAuthorization->getRight()
        );
        $this->assertNull(
            $beforeAuthorization->getAuthorized()
        );
    }
}
