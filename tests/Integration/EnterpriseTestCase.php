<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Integration;

use OxidEsales\EshopCommunity\Internal\Framework\Edition\Edition;
use OxidEsales\EshopCommunity\Internal\Framework\Edition\EditionDirectoriesLocator;

abstract class EnterpriseTestCase extends TokenTestCase
{
    public function setUp(): void
    {
        if (!(new EditionDirectoriesLocator())->getEditionRootPath(Edition::Enterprise)) {
            $this->markTestSkipped('Skip EE related tests for CE/PE edition');
            return;
        }

        parent::setUp();
    }
}
