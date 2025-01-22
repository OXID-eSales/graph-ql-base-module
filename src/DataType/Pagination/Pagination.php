<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\DataType\Pagination;

use Doctrine\DBAL\Query\QueryBuilder;
use GraphQL\Error\Error;
use TheCodingMachine\GraphQLite\Annotations\Factory;

final class Pagination
{
    /**
     * @throws Error
     */
    public function __construct(
        private readonly int $offset = 0,
        private readonly ?int $limit = null
    ) {
        if (
            $offset < 0 ||
            ($limit !== null && $limit <= 0)
        ) {
            throw new Error('PaginationFilter fields must be positive.');
        }
    }

    public function offset(): ?int
    {
        return $this->offset;
    }

    public function limit(): ?int
    {
        return $this->limit;
    }

    public function addPaginationToQuery(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->setFirstResult($this->offset);

        if ($this->limit > 0) {
            $queryBuilder->setMaxResults($this->limit);
        }
    }

    /** @Factory(name="PaginationFilterInput", default=true) */
    public static function fromUserInput(
        int $offset = 0,
        ?int $limit = null
    ): self {
        return new self(
            $offset,
            $limit
        );
    }
}
