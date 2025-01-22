<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\DataType\Filter;

use Doctrine\DBAL\Query\QueryBuilder;
use InvalidArgumentException;
use TheCodingMachine\GraphQLite\Annotations\Factory;
use TheCodingMachine\GraphQLite\Types\ID;

use function strtoupper;

class IDFilter implements FilterInterface
{
    public function __construct(private readonly ID $id)
    {
    }

    public function equals(): ID
    {
        return $this->id;
    }

    public function addToQuery(QueryBuilder $queryBuilder, string $field): void
    {
        /** @var array $from */
        $from = $queryBuilder->getQueryPart('from');

        if ($from === []) {
            throw new InvalidArgumentException('QueryBuilder is missing "from" SQL part');
        }
        $table = $from[0]['alias'] ?? $from[0]['table'];

        $queryBuilder->andWhere(sprintf('%s.%s = :%s', $table, strtoupper($field), $field))
            ->setParameter(':' . $field, $this->id);
    }

    /**
     * @Factory(name="IDFilterInput", default=true)
     */
    public static function fromUserInput(
        ID $id
    ): self {
        return new self(
            $id
        );
    }
}
