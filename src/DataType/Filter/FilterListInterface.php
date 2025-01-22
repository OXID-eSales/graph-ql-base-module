<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\DataType\Filter;

interface FilterListInterface
{
    /**
     * @return FilterInterface[]
     */
    public function getFilters(): array;

    public function withActiveFilter(?BoolFilter $boolFilter): self;

    public function getActive(): ?BoolFilter;
}
