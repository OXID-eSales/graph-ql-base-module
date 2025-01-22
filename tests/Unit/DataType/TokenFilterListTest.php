<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\DataType;

use OxidEsales\GraphQL\Base\DataType\Filter\BoolFilter;
use OxidEsales\GraphQL\Base\DataType\Filter\DateFilter;
use OxidEsales\GraphQL\Base\DataType\Filter\IDFilter;
use OxidEsales\GraphQL\Base\DataType\TokenFilterList;
use TheCodingMachine\GraphQLite\Types\ID;

class TokenFilterListTest extends DataTypeTestCase
{
    public function testDefaultFactory(): void
    {
        $tokenFilterList = new TokenFilterList();

        $expected = [
            'oxuserid' => null,
            'oxshopid' => null,
            'expires_at' => null,
        ];

        $this->assertSame($expected, $tokenFilterList->getFilters());
    }

    public function testFactory(): void
    {
        $expected = [
            'oxuserid' => new IDFilter(new ID('_userId')),
            'oxshopid' => new IDFilter(new ID(66)),
            'expires_at' => new DateFilter(null, ['2021-01-12 12:12:12', '2021-12-31 12:12:12']),
        ];

        $tokenFilterList = new TokenFilterList(...array_values($expected));

        $this->assertSame($expected, $tokenFilterList->getFilters());
    }

    public function testActiveFilter(): void
    {
        $tokenFilterList = new TokenFilterList();

        $this->assertNull($tokenFilterList->getActive());

        $tokenFilterList->withActiveFilter(new BoolFilter());

        $this->assertNull($tokenFilterList->getActive());
    }

    public function testWithUserFilter(): void
    {
        $filterList = new TokenFilterList();

        $this->assertNull($filterList->getUserFilter());

        $idFilter = new IDFilter(new ID('_userId'));
        $filterList = $filterList->withUserFilter($idFilter);

        $this->assertEquals($idFilter, $filterList->getUserFilter());
    }
}
