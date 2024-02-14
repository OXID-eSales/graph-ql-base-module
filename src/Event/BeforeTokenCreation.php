<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Event;

use Lcobucci\JWT\Builder;
use OxidEsales\GraphQL\Base\DataType\User;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeTokenCreation extends Event
{
    public function __construct(
        private readonly Builder $builder,
        private readonly User $user
    ) {
    }

    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
