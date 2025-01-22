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
use OxidEsales\GraphQL\Base\DataType\Filter\IntegerFilter;
use OxidEsales\GraphQL\Base\Tests\Unit\DataType\DataTypeTestCase;

class IntegerFilterTest extends DataTypeTestCase
{
    public function testThrowsExceptionOnNoInput(): void
    {
        $this->expectException(Exception::class);
        IntegerFilter::fromUserInput();
    }

    public function testGivesEquals(): void
    {
        $this->assertSame(
            2,
            (IntegerFilter::fromUserInput(
                2
            ))->equals()
        );
    }

    public function testGivesLowerThan(): void
    {
        $this->assertSame(
            2,
            (IntegerFilter::fromUserInput(
                null,
                2
            ))->lessThan()
        );
    }

    public function testGivesGreaterThan(): void
    {
        $this->assertSame(
            2,
            (IntegerFilter::fromUserInput(
                null,
                null,
                2
            ))->greaterThan()
        );
    }

    public function testGivesParametersIfSet(): void
    {
        $integerFilter = IntegerFilter::fromUserInput(
            5,
            10,
            1,
            [
                0,
                10,
            ]
        );

        $this->assertSame(
            5,
            $integerFilter->equals()
        );
        $this->assertSame(
            10,
            $integerFilter->lessThan()
        );
        $this->assertSame(
            1,
            $integerFilter->greaterThan()
        );
        $this->assertSame(
            [
                0,
                10,
            ],
            $integerFilter->between()
        );
    }

    public static function invalidBetweens(): array
    {
        return [
            [
                [],
            ],
            [
                [1],
            ],
            [
                [null, 1],
            ],
            [
                [1, null],
            ],
            [
                [1, 2, 3],
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
        IntegerFilter::fromUserInput(
            null,
            null,
            null,
            $between
        );
    }

    public function testAddQueryPartWithNoFrom(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $integerFilter = IntegerFilter::fromUserInput(6088077);

        $this->expectException(InvalidArgumentException::class);
        $integerFilter->addToQuery($queryBuilder, 'db_field');
    }

    public function testAddQueryPartEquals(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();

        $number = 6088077;
        $integerFilter = IntegerFilter::fromUserInput($number);

        $queryBuilder->select()->from('db_table');
        $integerFilter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals($where::TYPE_AND, $where->getType());
        $this->assertEquals('db_table.DB_FIELD = :db_field_eq', (string)$where);
        $this->assertEquals($number, $queryBuilder->getParameter(':db_field_eq'));
    }

    public function testAddQueryPartBetween(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();

        $numbers = [
            6088077,
            346901,
        ];
        $integerFilter = IntegerFilter::fromUserInput(null, null, null, $numbers);

        $queryBuilder->select()->from('db_table');
        $integerFilter->addToQuery($queryBuilder, 'db_field');

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
        $integerFilter = IntegerFilter::fromUserInput(6088077);

        $queryBuilder->select()->from('db_table', 'db_table_alias');
        $integerFilter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals('db_table_alias.DB_FIELD = :db_field_eq', (string)$where);
    }
}
