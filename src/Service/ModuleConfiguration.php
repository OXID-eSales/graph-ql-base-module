<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\GraphQL\Base\Exception\MissingSignatureKey;

use function bin2hex;
use function random_bytes;
use function strlen;

/**
 * The current implementation stores the signature key in
 * the config table. This should be changed eventually.
 */
class ModuleConfiguration
{
    public const SIGNATUREKEYNAME = 'sJsonWebTokenSignature';

    public const LIFETIMENAME = 'sJsonWebTokenLifetime';

    public const REFRESHLIFETIMENAME = 'sRefreshTokenLifetime';

    public const QUOTANAME = 'sJsonWebTokenUserQuota';

    public const COOKIE_SETTING_NAME = 'sFingerprintCookieMode';

    public const COOKIE_SETTING_SAME = 'sameOrigin';

    public const COOKIE_SETTING_CROSS = 'crossOrigin';

    /** @var array<string, string> */
    private array $lifetimeMap = [
        '1min' => '+1 minute',
        '5min' => '+5 minutes',
        '10min' => '+10 minutes',
        '15min' => '+15 minutes',
        '30min' => '+30 minutes',
        '1hrs' => '+1 hour',
        '3hrs' => '+3 hours',
        '8hrs' => '+8 hours',
        '12hrs' => '+12 hours',
        '24hrs' => '+24 hours',
        '7days' => '+7 days',
        '30days' => '+30 days',
        '60days' => '+60 days',
        '90days' => '+90 days',
    ];

    public function __construct(
        private readonly ModuleSettingServiceInterface $moduleSettingService
    ) {
    }

    public function generateSignatureKey(): string
    {
        return bin2hex(random_bytes(64));
    }

    /**
     * @throws MissingSignatureKey
     *
     * @return non-empty-string
     */
    public function getSignatureKey(): string
    {
        $signature = $this->moduleSettingService
            ->getString(static::SIGNATUREKEYNAME, 'oe_graphql_base')
            ->trim()
            ->toString();

        if (strlen($signature) < 64) {
            throw new MissingSignatureKey();
        }

        return $signature;
    }

    public function generateAndSaveSignatureKey(): void
    {
        $this->moduleSettingService->saveString(
            static::SIGNATUREKEYNAME,
            $this->generateSignatureKey(),
            'oe_graphql_base'
        );
    }

    public function getTokenLifeTime(): string
    {
        $key = $this->moduleSettingService
            ->getString(static::LIFETIMENAME, 'oe_graphql_base')
            ->toString();

        return $this->lifetimeMap[$key] ?? $this->lifetimeMap['8hrs'];
    }

    public function getRefreshTokenLifeTime(): string
    {
        $key = $this->moduleSettingService
            ->getString(static::REFRESHLIFETIMENAME, 'oe_graphql_base')
            ->toString();

        return $this->lifetimeMap[$key] ?? $this->lifetimeMap['24hrs'];
    }

    public function getUserTokenQuota(): int
    {
        return $this->moduleSettingService->getInteger(static::QUOTANAME, 'oe_graphql_base');
    }

    public function getCookieSetting(): string
    {
        $setting = $this->moduleSettingService
            ->getString(static::COOKIE_SETTING_NAME, 'oe_graphql_base')
            ->toString();

        return $setting;
    }
}
