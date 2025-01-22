<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Integration\Framework\DataType;

use OxidEsales\GraphQL\Base\DataType\Filter\BoolFilter;
use OxidEsales\GraphQL\Base\DataType\Filter\FloatFilter;
use OxidEsales\GraphQL\Base\DataType\Filter\IntegerFilter;
use OxidEsales\GraphQL\Base\DataType\Filter\StringFilter;
use TheCodingMachine\GraphQLite\Annotations\Factory;

class TestFilterFactory
{
    /**
     * @Factory
     */
    public static function createTestFilter(
        ?BoolFilter $boolFilter = null,
        ?FloatFilter $floatFilter = null,
        ?IntegerFilter $integerFilter = null,
        ?StringFilter $stringFilter = null
    ): TestFilter {
        return new TestFilter(
            $boolFilter,
            $floatFilter,
            $integerFilter,
            $stringFilter
        );
    }
}
