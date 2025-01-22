<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Codeception\Config;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext;
use OxidEsales\EshopCommunity\Internal\Framework\Configuration\DataObject\DatabaseConfiguration;
use OxidEsales\Codeception\Module\Database;
use OxidEsales\Codeception\Module\Database\DatabaseDefaultsFileGenerator;
use OxidEsales\Facts\Config\ConfigFile;
use Symfony\Component\Filesystem\Path;

if ($shopRootPath = getenv('SHOP_ROOT_PATH')) {
    require_once(Path::join($shopRootPath, 'source', 'bootstrap.php'));
}

class CodeceptionParametersProvider {

    public function getParameters(): array
    {
        $basicContext = new BasicContext();
        $php = (getenv('PHPBIN')) ?: 'php';

        return [
            'SHOP_URL' => $basicContext->getShopBaseUrl(),
            'SHOP_SOURCE_PATH' => $basicContext->getSourcePath(),
            'VENDOR_PATH' => $basicContext->getVendorPath(),
            'DB_NAME' => (new DatabaseConfiguration($basicContext->getDatabaseUrl()))->getName(),
            'DB_USERNAME' => (new DatabaseConfiguration($basicContext->getDatabaseUrl()))->getUser(),
            'DB_PASSWORD' => (new DatabaseConfiguration($basicContext->getDatabaseUrl()))->getPass(),
            'DB_HOST' => (new DatabaseConfiguration($basicContext->getDatabaseUrl()))->getHost(),
            'DB_PORT' => (new DatabaseConfiguration($basicContext->getDatabaseUrl()))->getPort(),
            'MODULE_DUMP_PATH' => $this->getModuleTestDataDumpFilePath(),
            'MYSQL_CONFIG_PATH' => $this->getMysqlConfigPath(),
            'PHP_BIN' => $php,
        ];
    }

    private function getModuleTestDataDumpFilePath()
    {
        return Path::join(__DIR__, '..', 'Support', 'Data', 'dump.sql');
    }

    private function getMysqlConfigPath()
    {
        $basicContext = new BasicContext();
        $configFile = new ConfigFile($basicContext->getSourcePath() . '/config.inc.php');

        $databaseDefaultsFileGenerator = new DatabaseDefaultsFileGenerator($configFile);

        return Database::generateStartupOptionsFile((new DatabaseConfiguration((new BasicContext())->getDatabaseUrl()))->getUser(), (new DatabaseConfiguration((new BasicContext())->getDatabaseUrl()))->getPass(), (new DatabaseConfiguration((new BasicContext())->getDatabaseUrl()))->getHost(), (new DatabaseConfiguration((new BasicContext())->getDatabaseUrl()))->getPort());
    }
}


