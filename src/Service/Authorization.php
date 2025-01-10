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
        private Token $tokenService,
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
        $event = new BeforeAuthorization(
            $this->tokenService->getToken(),
            $right
        );

        $this->eventDispatcher->dispatch(
            $event
        );

        $authByEvent = $event->getAuthorized();

        if (is_bool($authByEvent)) {
            return $authByEvent;
        }

        $userId = $this->tokenService->getTokenClaim(Token::CLAIM_USERID);
        $groups = $this->legacyService->getUserGroupIds($userId);

        foreach ($groups as $id) {
            if (isset($this->permissions[$id]) && in_array($right, $this->permissions[$id], true)) {
                return true;
            }
        }

        return false;
    }
}
