<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Event;

use Lcobucci\JWT\Builder;
use OxidEsales\GraphQL\Base\DataType\User;
use OxidEsales\GraphQL\Base\Event\BeforeTokenCreation;
use OxidEsales\GraphQL\Base\Tests\Unit\BaseTestCase;

class BeforeTokenCreationTest extends BaseTestCase
{
    public function testBasicGetters(): void
    {
        $userId = 'user-id';

        $builderMock = $this->getMockBuilder(Builder::class)->getMock();
        $beforeTokenCreation = new BeforeTokenCreation(
            $builderMock,
            new User($this->getUserModelStub($userId))
        );

        $this->assertInstanceOf(
            Builder::class,
            $beforeTokenCreation->getBuilder()
        );
        $this->assertInstanceOf(
            User::class,
            $beforeTokenCreation->getUser()
        );
        $this->assertSame(
            $userId,
            $beforeTokenCreation->getUser()->id()->val()
        );
    }
}
