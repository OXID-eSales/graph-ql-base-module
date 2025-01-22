<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\DataType\Pagination;

use OxidEsales\GraphQL\Base\DataType\Pagination\Pagination;
use OxidEsales\GraphQL\Base\Tests\Unit\DataType\DataTypeTestCase;

class PaginationTest extends DataTypeTestCase
{
    public function testReturnOnEmptyInitialization(): void
    {
        $this->assertSame(
            0,
            (new Pagination())->offset()
        );
        $this->assertSame(
            null,
            (new Pagination())->limit()
        );
    }

    public function testBasicPaginationFilter(): void
    {
        $pagination = Pagination::fromUserInput(
            1,
            2
        );
        $this->assertSame(
            1,
            $pagination->offset()
        );
        $this->assertSame(
            2,
            $pagination->limit()
        );
    }

    public function testDefaultNamedConstructor(): void
    {
        $pagination = Pagination::fromUserInput();

        $this->assertSame(
            0,
            $pagination->offset()
        );
        $this->assertNull(
            $pagination->limit()
        );
    }

    /**
     * @dataProvider paginationDataProvider
     *
     * @param mixed $offset
     * @param mixed $limit
     */
    public function testInvalidValuesOnPaginationFilter($offset, $limit): void
    {
        $this->expectExceptionMessage('PaginationFilter fields must be positive.');

        $pagination = Pagination::fromUserInput($offset, $limit);
        $pagination->offset();
        $pagination->limit();
    }

    public static function paginationDataProvider(): array
    {
        return [
            [0, 0],
            [0, -1],
            [-1, 1],
            [-1, null],
        ];
    }

    /**
     * @dataProvider addPaginationToQueryProvider
     */
    public function testAddPaginationToQuery(int $offset, ?int $limit): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $pagination = Pagination::fromUserInput($offset, $limit);

        $pagination->addPaginationToQuery($queryBuilder);

        $this->assertEquals($offset, $queryBuilder->getFirstResult());
        $this->assertEquals($limit, $queryBuilder->getMaxResults());
    }

    public static function addPaginationToQueryProvider(): array
    {
        return [
            [0, null],
            [0, 100],
            [5, null],
            [100, 10],
        ];
    }
}
