<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Codeception\Helper;

use Codeception\Module;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Exception;

class Acceptance extends Module
{
    public function _beforeSuite($settings = []): void // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $rootPath = ContainerFacade::get(BasicContextInterface::class)->getShopRootPath();
        $possiblePaths = [
            '/bin/oe-console',
            '/vendor/bin/oe-console',
        ];

        foreach ($possiblePaths as $path) {
            if (is_file($rootPath . $path)) {
                exec($rootPath . $path . ' oe:module:activate oe_graphql_base');

                return;
            }
        }

        throw new Exception('Could not find script "/bin/oe-console" to activate module');
    }
}
