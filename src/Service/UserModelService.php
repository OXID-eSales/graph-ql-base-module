<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Service;

use OxidEsales\GraphQL\Base\Infrastructure\Legacy;

/**
 * User model service
 */
class UserModelService
{
    public function __construct(
        private readonly Legacy $legacy,
    ) {
    }

    public function isPasswordChanged(string $userId, ?string $passwordNew): bool
    {
        $userModel = $this->legacy->getUserModel($userId);
        $currentPassword = $userModel->getFieldData('oxpassword');
        if (!$passwordNew || !$currentPassword) {
            return false;
        }

        return $currentPassword !== $passwordNew;
    }
}
