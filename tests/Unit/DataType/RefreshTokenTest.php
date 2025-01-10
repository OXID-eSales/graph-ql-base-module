<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\DataType;

use DateTime;
use Codeception\PHPUnit\TestCase;
use OxidEsales\GraphQL\Base\DataType\RefreshToken;
use OxidEsales\GraphQL\Base\Infrastructure\Model\RefreshToken as RefreshTokenModel;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RefreshToken::class)]
class RefreshTokenTest extends TestCase
{
    public function testModelInformationAccess(): void
    {
        $modelStub = $this->createStub(RefreshTokenModel::class);

        $sut = new RefreshToken($modelStub);
        $this->assertSame($modelStub, $sut->getEshopModel());
        $this->assertSame(RefreshTokenModel::class, $sut->getModelClass());
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

        $sut = new RefreshToken($modelMock);

        $this->assertSame($exampleTokenId, $sut->id()->val());
        $this->assertSame($exampleUserId, $sut->customerId()->val());
        $this->assertSame($exampleShopId, $sut->shopId()->val());
        $this->assertSame($exampleToken, $sut->token());

        $this->assertSame($exampleIssuedAt, $sut->createdAt()->format(DateTime::ATOM));
        $this->assertSame($exampleExpiresAt, $sut->expiresAt()->format(DateTime::ATOM));
    }
}
