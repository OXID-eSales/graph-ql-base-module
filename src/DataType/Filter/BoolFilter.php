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

use function strtoupper;

class BoolFilter implements FilterInterface
{
    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct(private readonly bool $equals = true)
    {
    }

    public function equals(): bool
    {
        return $this->equals;
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
            ->setParameter(':' . $field, $this->equals ? '1' : '0');
        // if equals is set, then no other conditions may apply
    }

    /**
     * @Factory(name="BoolFilterInput", default=true)
     */
    public static function fromUserInput(
        bool $equals
    ): self {
        return new self(
            $equals
        );
    }
}
