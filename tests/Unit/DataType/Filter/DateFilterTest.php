<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\DataType\Filter;

use DateTime;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Exception;
use InvalidArgumentException;
use OutOfBoundsException;
use OxidEsales\GraphQL\Base\DataType\Filter\DateFilter;
use OxidEsales\GraphQL\Base\Tests\Unit\DataType\DataTypeTestCase;

class DateFilterTest extends DataTypeTestCase
{
    public function testThrowsExceptionOnNoInput(): void
    {
        $this->expectException(Exception::class);
        DateFilter::fromUserInput();
    }

    public static function invalidBetweens(): array
    {
        return [
            [
                [],
            ],
            [
                [null, null, null],
            ],
            [
                ['foobar', null],
            ],
            [
                [null, 'foobar'],
            ],
            [
                [null, null],
            ],
        ];
    }

    /**
     * @dataProvider invalidBetweens
     */
    public function testThrowsExceptionOnInvalidBetween(
        array $between
    ): void {
        $this->expectException(OutOfBoundsException::class);
        DateFilter::fromUserInput(
            null,
            $between
        );
    }

    public function testThrowExceptionOnInvalidDateTimeBetween(): void
    {
        $this->expectException(Exception::class);
        DateFilter::fromUserInput(
            null,
            ['foo', 'bar']
        );
    }

    public function testThrowsExceptionOnInvalidEquals(): void
    {
        $this->expectException(Exception::class);
        DateFilter::fromUserInput(
            'foobar'
        );
    }

    public function testBasicDateFilter(): void
    {
        $filter = DateFilter::fromUserInput(
            '2020-01-30 12:37:21'
        );
        $this->assertSame(
            '2020-01-30T12:37:21+00:00',
            $filter->equals()->format(DateTime::ATOM)
        );

        $filter = DateFilter::fromUserInput(
            '2020-01-30 12:37:21 CET'
        );
        $this->assertSame(
            '2020-01-30T12:37:21+01:00',
            $filter->equals()->format(DateTime::ATOM)
        );

        $filter = DateFilter::fromUserInput(
            null,
            [
                '2020-01-30 12:37:21',
                '2020-01-30 12:37:22',
            ]
        );
        $this->assertSame(
            [
                '2020-01-30T12:37:21+00:00',
                '2020-01-30T12:37:22+00:00',
            ],
            [
                $filter->between()[0]->format(DateTime::ATOM),
                $filter->between()[1]->format(DateTime::ATOM),
            ]
        );
    }

    public function testAddQueryPartWithNoFrom(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $dateFilter = DateFilter::fromUserInput('2020-01-30 12:37:21');

        $this->expectException(InvalidArgumentException::class);
        $dateFilter->addToQuery($queryBuilder, 'db_field');
    }

    public function testAddQueryPartEquals(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $date = '2020-01-30 12:37:21';
        $dateFilter = DateFilter::fromUserInput($date);

        $queryBuilder->select()->from('db_table');
        $dateFilter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals($where::TYPE_AND, $where->getType());
        $this->assertEquals('db_table.DB_FIELD = :db_field_eq', (string)$where);
        $this->assertEquals($date, $queryBuilder->getParameter(':db_field_eq'));
    }

    public function testAddQueryPartBetween(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();

        $dates = [
            '2020-01-30 12:37:21',
            '2020-01-30 12:37:22',
        ];
        $dateFilter = DateFilter::fromUserInput(null, $dates);

        $queryBuilder->select()->from('db_table');
        $dateFilter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals($where::TYPE_AND, $where->getType());
        $this->assertEquals(
            'db_table.DB_FIELD BETWEEN :db_field_lower AND :db_field_upper',
            (string)$where
        );
        $this->assertEquals($dates[0], $queryBuilder->getParameter(':db_field_lower'));
        $this->assertEquals($dates[1], $queryBuilder->getParameter(':db_field_upper'));
    }

    public function testAddQueryPartWithAlias(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $dateFilter = DateFilter::fromUserInput('2020-01-30 12:37:21');

        $queryBuilder->select()->from('db_table', 'db_table_alias');
        $dateFilter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals('db_table_alias.DB_FIELD = :db_field_eq', (string)$where);
    }
}
