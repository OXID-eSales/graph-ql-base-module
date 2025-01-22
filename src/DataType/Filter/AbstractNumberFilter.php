<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\DataType\Filter;

use Doctrine\DBAL\Query\QueryBuilder;
use InvalidArgumentException;
use OutOfBoundsException;

abstract class AbstractNumberFilter
{
    abstract public function equals(): mixed;
    abstract public function lessThan(): mixed;
    abstract public function greaterThan(): mixed;
    abstract public function between(): ?array;

    public function addToQuery(QueryBuilder $queryBuilder, string $field): void
    {
        /** @var array $from */
        $from = $queryBuilder->getQueryPart('from');

        if ($from === []) {
            throw new InvalidArgumentException('QueryBuilder is missing "from" SQL part');
        }
        $table = $from[0]['alias'] ?? $from[0]['table'];

        if ($this->equals()) {
            $queryBuilder->andWhere(sprintf('%s.%s = :%s_eq', $table, strtoupper($field), $field))
                ->setParameter(':' . $field . '_eq', $this->equals());
            // if equals is set, then no other conditions may apply
            return;
        }

        if ($this->lessThan()) {
            $queryBuilder->andWhere(sprintf('%s.%s < :%s_lt', $table, strtoupper($field), $field))
                ->setParameter(':' . $field . '_lt', $this->lessThan());
        }

        if ($this->greaterThan()) {
            $queryBuilder->andWhere(sprintf('%s.%s > :%s_gt', $table, strtoupper($field), $field))
                ->setParameter(':' . $field . '_gt', $this->greaterThan());
        }

        if ($this->between()) {
            $where = sprintf('%s.%s BETWEEN :%s_less AND :%s_upper', $table, strtoupper($field), $field, $field);
            $queryBuilder->andWhere($where)
                ->setParameter(':' . $field . '_less', $this->between()[0])
                ->setParameter(':' . $field . '_upper', $this->between()[1]);
        }
    }

    protected function atLeastOneIsNotNull(mixed ...$values): bool
    {
        $result = array_map('is_null', $values);
        if (in_array(false, $result, true)) {
            return true;
        }
        return false;
    }

    protected static function checkRangeOfBetween(?array $between, callable $checkMethod): void
    {
        if (
            $between !== null && (
                count($between) !== 2 ||
                !$checkMethod($between[0]) ||
                !$checkMethod($between[1])
            )
        ) {
            throw new OutOfBoundsException();
        }
    }
}
