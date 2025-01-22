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

class TestFilter
{
    private $active;

    private $price;

    private $stock;

    private $title;

    public function __construct(
        ?BoolFilter $boolFilter = null,
        ?FloatFilter $floatFilter = null,
        ?IntegerFilter $integerFilter = null,
        ?StringFilter $stringFilter = null
    ) {
        $this->active = $boolFilter;
        $this->price = $floatFilter;
        $this->stock = $integerFilter;
        $this->title = $stringFilter;
    }

    public function __toString(): string
    {
        $s = 'active: ' . ($this->active->equals() ? 'true' : 'false') . PHP_EOL;
        $s .= 'price-eq: ' . $this->price->equals() . PHP_EOL;
        $s .= 'price-lt: ' . $this->price->lessThan() . PHP_EOL;
        $s .= 'price-gt: ' . $this->price->greaterThan() . PHP_EOL;
        $s .= 'price-between: ' . print_r($this->price->between(), true) . PHP_EOL;
        $s .= 'stock-eq: ' . $this->stock->equals() . PHP_EOL;
        $s .= 'stock-lt: ' . $this->stock->lessThan() . PHP_EOL;
        $s .= 'stock-gt: ' . $this->stock->greaterThan() . PHP_EOL;
        $s .= 'stock-between: ' . print_r($this->stock->between(), true) . PHP_EOL;
        $s .= 'title-eq: ' . $this->title->equals() . PHP_EOL;
        $s .= 'title-contains: ' . $this->title->contains() . PHP_EOL;
        $s .= 'title-beginsWith: ' . $this->title->beginsWith() . PHP_EOL;

        return $s;
    }
}
