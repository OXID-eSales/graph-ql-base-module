<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Infrastructure;

use OxidEsales\EshopCommunity\Internal\Container\ContainerBuilderFactory;
use OxidEsales\GraphQL\Base\Service\ModuleConfiguration;

/**
 * @codeCoverageIgnore
 */
class ModuleSetup
{
    public function __construct(
        private readonly ModuleConfiguration $moduleConfiguration
    ) {
    }

    public function runSetup(): void
    {
        $this->moduleConfiguration->generateAndSaveSignatureKey();
    }

    /**
     * Activation function for the module
     */
    public static function onActivate(): void
    {
        $containerBuilder = (new ContainerBuilderFactory())->create()->getContainer();
        $containerBuilder->compile();

        /** @var ModuleSetup $moduleSetup */
        $moduleSetup = $containerBuilder->get(self::class);
        $moduleSetup->runSetup();
    }

    /**
     * Deactivation function for the module
     */
    public static function onDeactivate(): void
    {
    }
}
