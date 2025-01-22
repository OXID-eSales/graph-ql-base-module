<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\DataType;

use DateTime;
use PHPUnit\Framework\TestCase;
use OxidEsales\GraphQL\Base\DataType\RefreshToken;
use OxidEsales\GraphQL\Base\Infrastructure\Model\RefreshToken as RefreshTokenModel;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RefreshToken::class)]
class RefreshTokenTest extends TestCase
{
    public function testModelInformationAccess(): void
    {
        $modelStub = $this->createStub(RefreshTokenModel::class);

        $refreshToken = new RefreshToken($modelStub);
        $this->assertSame($modelStub, $refreshToken->getEshopModel());
        $this->assertSame(RefreshTokenModel::class, $refreshToken->getModelClass());
    }

    public function testFields(): void
    {
        $modelMock = $this->createMock(RefreshTokenModel::class);

        $modelMock->method('getId')->willReturn($exampleTokenId = uniqid());
        $modelMock->method('getRawFieldData')->willReturnMap([
            ['oxuserid', $exampleUserId = uniqid()],
            ['oxshopid', $exampleShopId = uniqid()],
            ['token', $exampleToken = uniqid()],
            ['issued_at', $exampleIssuedAt = (new DateTime('now'))->format(DateTime::ATOM)],
            ['expires_at', $exampleExpiresAt = (new DateTime('+1 day'))->format(DateTime::ATOM)],
        ]);

        $refreshToken = new RefreshToken($modelMock);

        $this->assertSame($exampleTokenId, $refreshToken->id()->val());
        $this->assertSame($exampleUserId, $refreshToken->customerId()->val());
        $this->assertSame($exampleShopId, $refreshToken->shopId()->val());
        $this->assertSame($exampleToken, $refreshToken->token());

        $this->assertSame($exampleIssuedAt, $refreshToken->createdAt()->format(DateTime::ATOM));
        $this->assertSame($exampleExpiresAt, $refreshToken->expiresAt()->format(DateTime::ATOM));
    }
}
