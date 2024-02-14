<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Service;

use OxidEsales\GraphQL\Base\DataType\Filter\IDFilter;
use OxidEsales\GraphQL\Base\DataType\Pagination\Pagination;
use OxidEsales\GraphQL\Base\DataType\Sorting\TokenSorting;
use OxidEsales\GraphQL\Base\DataType\Token as TokenDataType;
use OxidEsales\GraphQL\Base\DataType\TokenFilterList;
use OxidEsales\GraphQL\Base\DataType\User as UserDataType;
use OxidEsales\GraphQL\Base\Exception\InvalidLogin;
use OxidEsales\GraphQL\Base\Exception\NotFound;
use OxidEsales\GraphQL\Base\Exception\UserNotFound;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy as LegacyInfrastructure;
use OxidEsales\GraphQL\Base\Infrastructure\ModuleSetup;
use OxidEsales\GraphQL\Base\Infrastructure\Repository as BaseRepository;
use OxidEsales\GraphQL\Base\Infrastructure\Token as TokenInfrastructure;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Token data access service
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) TODO: Consider reducing complexity of this class
 */
class TokenAdministration
{
    public function __construct(
        private readonly BaseRepository $repository,
        private readonly Authorization $authorization,
        private readonly Authentication $authentication,
        private readonly TokenInfrastructure $tokenInfrastructure,
        private readonly LegacyInfrastructure $legacyInfrastructure,
        private readonly ModuleSetup $moduleSetup
    ) {
    }

    /**
     * @return TokenDataType[]
     */
    public function tokens(
        TokenFilterList $filterList,
        Pagination $pagination,
        TokenSorting $sort
    ): array {
        if (!$this->canSeeTokens($filterList)) {
            throw new InvalidLogin('Unauthorized');
        }

        return $this->repository->getList(
            TokenDataType::class,
            $filterList,
            $pagination,
            $sort
        );
    }

    private function canSeeTokens(TokenFilterList $filterList): bool
    {
        if ($this->authorization->isAllowed('VIEW_ANY_TOKEN')) {
            return true;
        }

        //without right to view any token user can only add filter on own id or no filter on id
        $userFilter = $filterList->getUserFilter();
        if ($userFilter === null) {
            return true;
        }
        return $this->authentication->getUser()->id()->val() === $userFilter->equals()->val();
    }

    /**
     * @throws \OxidEsales\GraphQL\Base\Exception\NotFound
     */
    public function customerTokensDelete(?ID $customerId): int
    {
        $customerId = $customerId ?: $this->authentication->getUser()->id();

        if (!$this->canDeleteCustomerTokens($customerId)) {
            throw new InvalidLogin('Unauthorized');
        }

        try {
            /** @var UserDataType $user */
            $user = $this->repository->getById(
                (string)$customerId,
                UserDataType::class
            );
        } catch (NotFound) {
            throw new UserNotFound((string)$customerId);
        }

        return $this->tokenInfrastructure->tokenDelete($user);
    }

    private function canDeleteCustomerTokens(ID $customerId): bool
    {
        if ($this->authorization->isAllowed('INVALIDATE_ANY_TOKEN')) {
            return true;
        }

        return $this->authentication->getUser()->id()->val() === $customerId->val();
    }

    public function shopTokensDelete(): int
    {
        return $this->tokenInfrastructure->tokenDelete(null, null, $this->legacyInfrastructure->getShopId());
    }

    public function regenerateSignatureKey(): bool
    {
        $this->moduleSetup->runSetup();

        return true;
    }
}
