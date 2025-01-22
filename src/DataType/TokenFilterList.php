<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\DataType;

use OxidEsales\GraphQL\Base\DataType\Filter\BoolFilter;
use OxidEsales\GraphQL\Base\DataType\Filter\DateFilter;
use OxidEsales\GraphQL\Base\DataType\Filter\FilterListInterface;
use OxidEsales\GraphQL\Base\DataType\Filter\IDFilter;
use TheCodingMachine\GraphQLite\Annotations\Factory;

final class TokenFilterList implements FilterListInterface
{
    public function __construct(
        private readonly ?IDFilter $customerId = null,
        private readonly ?IDFilter $shopId = null,
        private readonly ?DateFilter $dateFilter = null
    ) {
    }

    public function withActiveFilter(?BoolFilter $boolFilter): self
    {
        return $this;
    }

    public function getActive(): ?BoolFilter
    {
        return null;
    }

    public function getUserFilter(): ?IDFilter
    {
        return $this->customerId;
    }

    public function withUserFilter(IDFilter $idFilter): self
    {
        return new self($idFilter, $this->shopId, $this->dateFilter);
    }

    /**
     * @return array{
     *                oxuserid: ?IDFilter,
     *                oxshopid: ?IDFilter,
     *                expires_at: ?DateFilter
     *                }
     */
    public function getFilters(): array
    {
        return [
            'oxuserid' => $this->customerId,
            'oxshopid' => $this->shopId,
            'expires_at' => $this->dateFilter,
        ];
    }

    /**
     * @Factory(name="TokenFilterList",default=true)
     */
    public static function fromUserInput(
        ?IDFilter $customerId,
        ?IDFilter $shopId = null,
        ?DateFilter $dateFilter = null
    ): self {
        return new self($customerId, $shopId, $dateFilter);
    }
}
