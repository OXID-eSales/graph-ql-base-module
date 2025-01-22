<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Service;

use OxidEsales\GraphQL\Base\Event\BeforeAuthorization;
use OxidEsales\GraphQL\Base\Framework\PermissionProviderInterface;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy as LegacyService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface;
use function is_bool;

class Authorization implements AuthorizationServiceInterface
{
    /** @var array<string, array<string>> */
    private array $permissions = [];

    /**
     * @param PermissionProviderInterface[] $permissionProviders
     */
    public function __construct(
        iterable $permissionProviders,
        private readonly EventDispatcherInterface $eventDispatcher,
        private Token $token,
        private readonly LegacyService $legacyService
    ) {
        foreach ($permissionProviders as $permissionProvider) {
            $this->permissions = array_merge_recursive(
                $this->permissions,
                $permissionProvider->getPermissions()
            );
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) TODO: Make usage of $subject argument
     */
    public function isAllowed(string $right, mixed $subject = null): bool
    {
        $beforeAuthorization = new BeforeAuthorization(
            $this->token->getToken(),
            $right
        );

        $this->eventDispatcher->dispatch(
            $beforeAuthorization
        );

        $authByEvent = $beforeAuthorization->getAuthorized();

        if (is_bool($authByEvent)) {
            return $authByEvent;
        }

        $userId = $this->token->getTokenClaim(Token::CLAIM_USERID);
        $groups = $this->legacyService->getUserGroupIds($userId);

        foreach ($groups as $group) {
            if (isset($this->permissions[$group]) && in_array($right, $this->permissions[$group], true)) {
                return true;
            }
        }

        return false;
    }
}
