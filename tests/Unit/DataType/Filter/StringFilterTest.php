<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\DataType\Filter;

use Generator;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Exception;
use InvalidArgumentException;
use OxidEsales\GraphQL\Base\DataType\Filter\StringFilter;
use OxidEsales\GraphQL\Base\Tests\Unit\DataType\DataTypeTestCase;

class StringFilterTest extends DataTypeTestCase
{
    public function testThrowsExceptionOnNoInput(): void
    {
        $this->expectException(Exception::class);
        StringFilter::fromUserInput();
    }

    public function testNeedsAtLeastOneParameter(): void
    {
        $this->assertSame(
            'equals',
            (StringFilter::fromUserInput('equals'))->equals()
        );

        $this->assertSame(
            'contains',
            (StringFilter::fromUserInput(null, 'contains'))->contains()
        );

        $this->assertSame(
            'beginsWith',
            (StringFilter::fromUserInput(null, null, 'beginsWith'))->beginsWith()
        );
    }

    public function testBasicStringFilter(): void
    {
        $filter = StringFilter::fromUserInput(
            'equals',
            'contains',
            'beginsWith'
        );
        $this->assertSame(
            'equals',
            $filter->equals()
        );
        $this->assertSame(
            'contains',
            $filter->contains()
        );
        $this->assertSame(
            'beginsWith',
            $filter->beginsWith()
        );
    }

    public function testAddQueryPartWithNoFrom(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $filter = StringFilter::fromUserInput('no_from');

        $this->expectException(InvalidArgumentException::class);
        $filter->addToQuery($queryBuilder, 'db_field');
    }

    public function testAddQueryPartEquals(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();

        $string = 'equals';
        $filter = StringFilter::fromUserInput($string);

        $queryBuilder->select()->from('db_table');
        $filter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals($where::TYPE_AND, $where->getType());
        $this->assertEquals('db_table.DB_FIELD = :db_field_eq', (string)$where);
        $this->assertEquals($string, $queryBuilder->getParameter(':db_field_eq'));
    }

    public function testAddQueryPartContains(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();

        $string = 'contains';
        $filter = StringFilter::fromUserInput(null, $string);

        $queryBuilder->select()->from('db_table');
        $filter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals($where::TYPE_AND, $where->getType());
        $this->assertEquals(
            'db_table.DB_FIELD LIKE :db_field_contain',
            (string)$where
        );
        $this->assertEquals('%' . $string . '%', $queryBuilder->getParameter(':db_field_contain'));
    }

    public function testAddQueryPartBegins(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();

        $string = 'begins';
        $filter = StringFilter::fromUserInput(null, null, $string);

        $queryBuilder->select()->from('db_table');
        $filter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals($where::TYPE_AND, $where->getType());
        $this->assertEquals(
            'db_table.DB_FIELD LIKE :db_field_begins',
            (string)$where
        );
        $this->assertEquals($string . '%', $queryBuilder->getParameter(':db_field_begins'));
    }

    public function testAddQueryPartWithAlias(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $filter = StringFilter::fromUserInput('with_alias');

        $queryBuilder->select()->from('db_table', 'db_table_alias');
        $filter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals('db_table_alias.DB_FIELD = :db_field_eq', (string)$where);
    }

    /** @dataProvider matchesDataProvider */
    public function testMatches(
        string $stringForTrueCase,
        string $stringForFalseCase,
        StringFilter $initFilter
    ): void {
        $this->assertTrue($initFilter->matches($stringForTrueCase));
        $this->assertFalse($initFilter->matches($stringForFalseCase));
    }

    public static function matchesDataProvider(): Generator
    {
        yield "test match equals" => [
            'stringForTrueCase' => 'test theme 1',
            'stringForFalseCase' => 'test theme 22',
            'initFilter' => new StringFilter(equals: 'TEST theme 1')
        ];

        yield "test match contains" => [
            'stringForTrueCase' => 'test abc theme',
            'stringForFalseCase' => 'test xyz theme',
            'initFilter' => new StringFilter(contains: 'aBC')
        ];

        yield "test match begins with" => [
            'stringForTrueCase' => 'this start',
            'stringForFalseCase' => 'this does not start with',
            'initFilter' => new StringFilter(beginsWith: 'this START')
        ];

        yield "test match begins with and contains" => [
            'stringForTrueCase' => 'this start with abc',
            'stringForFalseCase' => 'this does not start with abc',
            'initFilter' => new StringFilter(beginsWith: 'THIS start', contains: 'abc')
        ];

        yield "test match equals and contains" => [
            'stringForTrueCase' => 'this is abc',
            'stringForFalseCase' => 'this is not abc',
            'initFilter' => new StringFilter(equals: 'This Is Abc', contains: 'ABC')
        ];
    }
}
