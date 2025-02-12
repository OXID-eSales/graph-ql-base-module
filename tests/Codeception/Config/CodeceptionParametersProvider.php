<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Codeception\Config;

use OxidEsales\Codeception\Module\Database;
use OxidEsales\EshopCommunity\Internal\Framework\Configuration\DataObject\DatabaseConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Edition\Edition;
use OxidEsales\EshopCommunity\Internal\Framework\Edition\EditionDirectoriesLocator;
use OxidEsales\EshopCommunity\Internal\Framework\Env\DotenvLoader;
use OxidEsales\EshopCommunity\Internal\Framework\FileSystem\DirectoryNotExistentException;
use OxidEsales\EshopCommunity\Internal\Framework\FileSystem\ProjectDirectoriesLocator;
use OxidEsales\EshopCommunity\Internal\Framework\FileSystem\ProjectRootLocator;
use Symfony\Component\Filesystem\Path;

class CodeceptionParametersProvider
{
    private DatabaseConfiguration $dbConfig;

    public function getParameters(): array
    {
        $this->loadEnvironmentVariables();

        $this->dbConfig = (new DatabaseConfiguration(getenv('OXID_DB_URL')));
        return [
            'SHOP_URL' => getenv('OXID_SHOP_BASE_URL'),
            'PROJECT_ROOT' => $this->getProjectRoot(),
            'VENDOR_PATH' => (new ProjectDirectoriesLocator())->getVendorPath(),
            'SOURCE_RELATIVE_PACKAGE_PATH' => $this->getSourceRelativePackagePath(),
            'DB_NAME' => $this->getDbName(),
            'DB_USERNAME' => $this->getDbUser(),
            'DB_PASSWORD' => $this->getDbPass(),
            'DB_HOST' => $this->getDbHost(),
            'DB_PORT' => $this->getDbPort(),
            'DUMP_PATH' => $this->getTestDataDumpFilePath(),
            'MODULE_DUMP_PATH' => $this->getCodeceptionSpecificFixtureFilePath(),
            'FIXTURES_PATH' => $this->getTestFixtureSqlFilePath(),
            'OUT_DIRECTORY' => (new ProjectDirectoriesLocator())->getOutPath(),
            'OUT_DIRECTORY_FIXTURES' => $this->getOutDirectoryFixturesPath(),
            'MYSQL_CONFIG_PATH' => $this->generateMysqlStarUpConfigurationFile(),
            'SELENIUM_SERVER_PORT' => getenv('SELENIUM_SERVER_PORT') ?: '4444',
            'SELENIUM_SERVER_HOST' => getenv('SELENIUM_SERVER_HOST') ?: 'selenium',
            'PHP_BIN' => (getenv('PHPBIN')) ?: 'php',
            'SCREEN_SHOT_URL' => getenv('CC_SCREEN_SHOTS_URL') ?: '',
            'BROWSER' => getenv('BROWSER_NAME') ?: 'chrome',
            'THEME_ID' => getenv('THEME_ID') ?: 'apex',
            'MAIL_HOST' => getenv('MAIL_HOST') ?: 'mailpit',
            'MAIL_WEB_PORT' => getenv('MAIL_WEB_PORT') ?: '8025',
        ];
    }

    private function getSourceRelativePackagePath(): string
    {
        return(str_replace($this->getProjectRoot(), '..', __DIR__) . '/../../../');
    }

    private function getCodeceptionSpecificFixtureFilePath(): string
    {
        return Path::join(__DIR__, '../Support/Data', 'dump.sql');
    }

    private function getTestDataDumpFilePath(): string
    {
        return Path::join(
            $this->getShopTestPath(),
            '/Codeception/Support/_generated/shop-dump.sql'
        );
    }

    private function getTestFixtureSqlFilePath(): string
    {
        return Path::join(
            $this->getShopTestPath(),
            '/Codeception/Support/Data/dump.sql',
        );
    }

    private function getOutDirectoryFixturesPath(): string
    {
        return Path::join(
            $this->getShopTestPath(),
            '/Codeception/Support/Data/out',
        );
    }

    private function getShopTestPath(): string
    {
        try {
            $testsPath = Path::join(
                (new EditionDirectoriesLocator())->getEditionRootPath(Edition::Enterprise),
                'Tests'
            );
        } catch (DirectoryNotExistentException) {
            $testsPath = Path::join(
                $this->getProjectRoot(),
                'tests'
            );
        }
        return $testsPath;
    }

    private function generateMysqlStarUpConfigurationFile(): string
    {
        return Database::generateStartupOptionsFile(
            $this->getDbUser(),
            $this->getDbPass(),
            $this->getDbHost(),
            $this->getDbPort(),
        );
    }

    private function getDbName(): string
    {
        return getenv('DB_NAME') ?: $this->dbConfig->getName();
    }

    private function getDbUser(): string
    {
        return getenv('DB_USERNAME') ?: $this->dbConfig->getUser();
    }

    private function getDbPass(): string
    {
        return getenv('DB_PASSWORD') ?: $this->dbConfig->getPass();
    }

    private function getDbHost(): string
    {
        return getenv('DB_HOST') ?: $this->dbConfig->getHost();
    }

    private function getDbPort(): int
    {
        return (int) getenv('DB_PORT') ?: $this->dbConfig->getPort();
    }

    private function loadEnvironmentVariables(): void
    {
        (new DotenvLoader($this->getProjectRoot()))->loadEnvironmentVariables();
    }

    private function getProjectRoot(): string
    {
        return (new ProjectRootLocator())->getProjectRoot();
    }
}
