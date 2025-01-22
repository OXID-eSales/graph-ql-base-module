<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\DataType;

use PHPUnit\Framework\TestCase;
use Lcobucci\JWT\UnencryptedToken;
use OxidEsales\GraphQL\Base\DataType\Login;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Login::class)]
class LoginTest extends TestCase
{
    public function testFields(): void
    {
        $refreshToken = uniqid();
        $accessToken = $this->createConfiguredStub(UnencryptedToken::class, [
            'toString' => $accessTokenContent = uniqid()
        ]);

        $login = new Login(
            refreshToken: $refreshToken,
            accessToken: $accessToken
        );

        $this->assertSame($refreshToken, $login->refreshToken());
        $this->assertSame($accessTokenContent, $login->accessToken());
    }
}
