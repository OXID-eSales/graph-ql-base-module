<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\DataType\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use InvalidArgumentException;
use OxidEsales\GraphQL\Base\DataType\Filter\IDFilter;
use OxidEsales\GraphQL\Base\Tests\Unit\DataType\DataTypeTestCase;
use TheCodingMachine\GraphQLite\Types\ID;

class IDFilterTest extends DataTypeTestCase
{
    public function testBasicIDFilter(): void
    {
        $idFilter = IDFilter::fromUserInput(
            new ID('test')
        );
        $this->assertSame(
            'test',
            (string)$idFilter->equals()
        );
    }

    public function testAddQueryPartWithNoFrom(): void
    {
        $connectionMock = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder = new QueryBuilder($connectionMock);
        $idFilter = IDFilter::fromUserInput(new ID('106d2528c6a9796fbd13cd30de6decf1'));

        $this->expectException(InvalidArgumentException::class);
        $idFilter->addToQuery($queryBuilder, 'db_field');
    }

    public function testAddQueryPart(): void
    {
        $connectionMock = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder = new QueryBuilder($connectionMock);

        $id = '106d2528c6a9796fbd13cd30de6decf1';
        $idFilter = IDFilter::fromUserInput(new ID($id));

        $queryBuilder->select()->from('db_table');
        $idFilter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals($where::TYPE_AND, $where->getType());
        $this->assertEquals('db_table.DB_FIELD = :db_field', (string)$where);
        $this->assertEquals($id, $queryBuilder->getParameter(':db_field'));
    }

    public function testAddQueryPartWithAlias(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $idFilter = IDFilter::fromUserInput(new ID('106d2528c6a9796fbd13cd30de6decf1'));

        $queryBuilder->select()->from('db_table', 'db_table_alias');
        $idFilter->addToQuery($queryBuilder, 'db_field');

        /** @var CompositeExpression $where */
        $where = $queryBuilder->getQueryPart('where');

        $this->assertEquals('db_table_alias.DB_FIELD = :db_field', (string)$where);
    }
}
