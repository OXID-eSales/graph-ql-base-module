<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\DataType\Filter;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Exception;
use InvalidArgumentException;
use OxidEsales\GraphQL\Base\DataType\Filter\FloatFilter;
use OxidEsales\GraphQL\Base\Tests\Unit\DataType\DataTypeTestCase;

class FloatFilterTest extends DataTypeTestCase
{
    public function testThrowsExceptionOnNoInput(): void
    {
        $this->expectException(Exception::class);
        FloatFilter::fromUserInput();
    }

    public function testGivesEquals(): void
    {
        $this->assertSame(
            2.0,
            (FloatFilter::fromUserInput(
                2.0
            ))->equals()
        );
    }

    public function testGivesLowerThan(): void
    {
        $this->assertSame(
            2.0,
            (FloatFilter::fromUserInput(
                null,
                2.0
            ))->lessThan()
        );
    }

    public function testGivesGreaterThan(): void
    {
        $this->assertSame(
            2.0,
            (FloatFilter::fromUserInput(
                null,
                null,
                2.0
            ))->greaterThan()
        );
    }

    public function testGivesParametersIfSet(): void
    {
        $floatFilter = FloatFilter::fromUserInput(
            5.0,
            10.0,
            1.0,
            [
                1.0,
                10.0,
            ]
        );

        $this->assertSame(
            5.0,
            $floatFilter->equals()
        );
        $this->assertSame(
            10.0,
            $floatFilter->lessThan()
        );
        $this->assertSame(
            1.0,
            $floatFilter->greaterThan()
        );
        $this->assertSame(
            [
                1.0,
                10.0,
            ],
            $floatFilter->between()
        );
    }

    public static function invalidBetweens(): array
    {
        return [
            [
                [],
            ],
            [
                [1.0],
            ],
            [
                [null, 1.0],
            ],
            [
                [1.0, null],
            ],
            [
                [1.0, 2.0, 3.0],
            ],
        ];
    }

    /**
     * @dataProvider invalidBetweens
     */
    public function testThrowsExceptionOnInvalidBetween(
        array $between
    ): void {
        $this->expectException(Exception::class);
        FloatFilter::fromUserInput(
            null,
            null,
            null,
            $between
        );
    }

    public function testAddQueryPartWithNoFrom(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $floatFilter = FloatFilter::fromUserInput(608.8077);

        $this->expectException(InvalidArgumentException::class);
        $floatFilter->addToQuery($queryBuilder, 'db_field');
    }

    public function testAddQueryPartEquals(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();

        $number = 608.8077;
        $floatFilter = FloatFilter::fromUserInput($number);

        $queryBuilder->select()->from('db_table');
        $floatFilter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals($where::TYPE_AND, $where->getType());
        $this->assertEquals('db_table.DB_FIELD = :db_field_eq', (string)$where);
        $this->assertEquals($number, $queryBuilder->getParameter(':db_field_eq'));
    }

    public function testAddQueryPartLowerThan(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();

        $number = 608.8077;
        $floatFilter = FloatFilter::fromUserInput(null, $number);

        $queryBuilder->select()->from('db_table');
        $floatFilter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals($where::TYPE_AND, $where->getType());
        $this->assertEquals('db_table.DB_FIELD < :db_field_lt', (string)$where);
        $this->assertEquals($number, $queryBuilder->getParameter(':db_field_lt'));
    }

    public function testAddQueryPartGreaterThan(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();

        $number = 608.8077;
        $floatFilter = FloatFilter::fromUserInput(null, null, $number);

        $queryBuilder->select()->from('db_table');
        $floatFilter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals($where::TYPE_AND, $where->getType());
        $this->assertEquals('db_table.DB_FIELD > :db_field_gt', (string)$where);
        $this->assertEquals($number, $queryBuilder->getParameter(':db_field_gt'));
    }

    public function testAddQueryPartBetween(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();

        $numbers = [
            608.8077,
            3469.01,
        ];
        $floatFilter = FloatFilter::fromUserInput(null, null, null, $numbers);

        $queryBuilder->select()->from('db_table');
        $floatFilter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals($where::TYPE_AND, $where->getType());
        $this->assertEquals(
            'db_table.DB_FIELD BETWEEN :db_field_less AND :db_field_upper',
            (string)$where
        );
        $this->assertEquals($numbers[0], $queryBuilder->getParameter(':db_field_less'));
        $this->assertEquals($numbers[1], $queryBuilder->getParameter(':db_field_upper'));
    }

    public function testAddQueryPartWithAlias(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $floatFilter = FloatFilter::fromUserInput(608.8077);

        $queryBuilder->select()->from('db_table', 'db_table_alias');
        $floatFilter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals('db_table_alias.DB_FIELD = :db_field_eq', (string)$where);
    }
}
