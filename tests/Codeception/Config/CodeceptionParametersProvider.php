<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Codeception\Config;

use OxidEsales\Codeception\Module\Database;
use OxidEsales\Codeception\Module\Database\DatabaseDefaultsFileGenerator;
use OxidEsales\Facts\Facts;
use Symfony\Component\Filesystem\Path;

class CodeceptionParametersProvider {

    public function getParameters(): array
    {
        $facts = new Facts();
        $php = (getenv('PHPBIN')) ?: 'php';

        return [
            'SHOP_URL' => $facts->getShopUrl(),
            'SHOP_SOURCE_PATH' => $facts->getSourcePath(),
            'VENDOR_PATH' => $facts->getVendorPath(),
            'DB_NAME' => $facts->getDatabaseName(),
            'DB_USERNAME' => $facts->getDatabaseUserName(),
            'DB_PASSWORD' => $facts->getDatabasePassword(),
            'DB_HOST' => $facts->getDatabaseHost(),
            'DB_PORT' => $facts->getDatabasePort(),
            'MODULE_DUMP_PATH' => $this->getModuleTestDataDumpFilePath(),
            'MYSQL_CONFIG_PATH' => $this->generateMysqlStarUpConfigurationFile(),
            'PHP_BIN' => $php,
        ];
    }

    private function getModuleTestDataDumpFilePath()
    {
        return Path::join(__DIR__, '..', 'Support', 'Data', 'dump.sql');
    }

    private function generateMysqlStarUpConfigurationFile(): string
    {
        $facts = new Facts();

        return Database::generateStartupOptionsFile(
            $facts->getDatabaseUserName(),
            $facts->getDatabasePassword(),
            $facts->getDatabaseHost(),
            $facts->getDatabasePort(),
        );
    }
}


