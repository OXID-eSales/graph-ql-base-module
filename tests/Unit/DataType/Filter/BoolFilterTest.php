<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\DataType\Filter;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use InvalidArgumentException;
use OxidEsales\GraphQL\Base\DataType\Filter\BoolFilter;
use OxidEsales\GraphQL\Base\Tests\Unit\DataType\DataTypeTestCase;

class BoolFilterTest extends DataTypeTestCase
{
    public function testReturnsTrueOnEmptyInitialization(): void
    {
        $this->assertTrue((new BoolFilter())->equals());
    }

    /**
     * @dataProvider boolDataProvider
     */
    public function testReturnsGivenEqualValue(bool $userInput, bool $returnValue): void
    {
        $this->assertSame(
            $returnValue,
            BoolFilter::fromUserInput($userInput)->equals()
        );
    }

    public static function boolDataProvider(): array
    {
        return [
            'equals returns false, if false is given' => [false, false],
            'equals returns true, if true is given' => [true, true],
        ];
    }

    public function testAddQueryPartWithNoFrom(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $boolFilter = BoolFilter::fromUserInput(true);

        $this->expectException(InvalidArgumentException::class);
        $boolFilter->addToQuery($queryBuilder, 'db_field');
    }

    /**
     * @dataProvider addQueryPartProvider
     */
    public function testAddQueryPart(bool $filterValue): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $boolFilter = BoolFilter::fromUserInput($filterValue);

        $queryBuilder->select()->from('db_table');
        $boolFilter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals($where::TYPE_AND, $where->getType());
        $this->assertEquals('db_table.DB_FIELD = :db_field', (string)$where);
        $this->assertEquals((int)$filterValue, $queryBuilder->getParameter(':db_field'));
    }

    public static function addQueryPartProvider(): array
    {
        return [[true], [false]];
    }

    public function testAddQueryPartWithAlias(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $boolFilter = BoolFilter::fromUserInput(true);

        $queryBuilder->select()->from('db_table', 'db_table_alias');
        $boolFilter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals('db_table_alias.DB_FIELD = :db_field', (string)$where);
    }
}
