<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Service;

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
        private readonly BaseRepository $baseRepository,
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
        TokenFilterList $tokenFilterList,
        Pagination $pagination,
        TokenSorting $tokenSorting
    ): array {
        if (!$this->canSeeTokens($tokenFilterList)) {
            throw new InvalidLogin('Unauthorized');
        }

        return $this->baseRepository->getList(
            TokenDataType::class,
            $tokenFilterList,
            $pagination,
            $tokenSorting
        );
    }

    private function canSeeTokens(TokenFilterList $tokenFilterList): bool
    {
        if ($this->authorization->isAllowed('VIEW_ANY_TOKEN')) {
            return true;
        }

        //without right to view any token user can only add filter on own id or no filter on id
        $userFilter = $tokenFilterList->getUserFilter();
        if ($userFilter === null) {
            return true;
        }
        return $this->authentication->getUser()->id()->val() === $userFilter->equals()->val();
    }

    /**
     * @throws NotFound
     */
    public function customerTokensDelete(?ID $customerId): int
    {
        $customerId = $customerId ?: $this->authentication->getUser()->id();

        if (!$this->canDeleteCustomerTokens($customerId)) {
            throw new InvalidLogin('Unauthorized');
        }

        try {
            /** @var UserDataType $user */
            $user = $this->baseRepository->getById(
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
