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
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
final class TokenFilterList implements FilterListInterface
{
    public function __construct(
        #[Field]
        private readonly ?IDFilter $customerId = null,
        #[Field]
        private readonly ?IDFilter $shopId = null,
        #[Field]
        private readonly ?DateFilter $expiresAt = null
    ) {
    }

    public function withActiveFilter(?BoolFilter $active): self
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

    public function withUserFilter(IDFilter $user): self
    {
        return new self($user, $this->shopId, $this->expiresAt);
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
            'expires_at' => $this->expiresAt,
        ];
    }
}
