<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Integration;

use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\UtilsObject;
use OxidEsales\Eshop\Core\ConfigFile;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\TestingLibrary\ModuleLoader;
use OxidEsales\TestingLibrary\Services\ModuleInstaller\ModuleInstaller;

abstract class MultishopTestCase extends EnterpriseTestCase
{
    public function setUp(): void
    {
        $this->setGETRequestParameter('shp', '1');
        static::$container = null;

        parent::setUp();

        $this->ensureShop(2);
        $this->cleanupCachedRegistry();
    }

    public function tearDown(): void
    {
        $this->cleanupCachedRegistry();

        parent::tearDown();
    }

    protected function ensureShop(int $shopId = 2): void
    {
        $database = DatabaseProvider::getDb();

        $shop = oxNew(Shop::class);

        if ($shop->load($shopId)) {
            return;
        }

        $shop->assign([
            'OXID' => $shopId,
            'OXACTIVE' => 1,
            'OXNAME' => 'Second shop',
        ]);
        $shop->save();

        $copyVars = [
            'aLanguages',
        ];

        // copy language settings from shop 1
        $database->execute(
            "INSERT INTO oxconfig (oxid, oxshopid, oxvarname, oxvartype, oxvarvalue, oxmodule)
            SELECT MD5(RAND()), {$shopId} AS oxshopid, oxvarname, oxvartype, oxvarvalue, oxmodule FROM oxconfig
            WHERE oxshopid = '1'
              AND oxvarname IN ( '" . implode("', '", $copyVars) . "')"
        );

        $container         = ContainerFactory::getInstance()->getContainer();
        $shopConfiguration = $container->get(ShopConfigurationDaoBridgeInterface::class)->get();
        Registry::getConfig()->setShopId($shopId);
        $container->get(ShopConfigurationDaoBridgeInterface::class)->save($shopConfiguration);

        $metaData = oxNew(DbMetaDataHandler::class);
        $metaData->updateViews();

        $moduleInstaller = new ModuleInstaller(Registry::getConfig());
        $moduleInstaller->switchToShop($shopId);

        $testConfig = $this->getTestConfig();
        $aInstallModules = $testConfig->getModulesToActivate();

        $moduleLoader = new ModuleLoader();
        $moduleLoader->activateModules($aInstallModules);
    }

    protected function cleanupCachedRegistry(): void
    {
        Registry::getConfig()->reinitialize();
        $utilsObject = UtilsObject::getInstance();
        $utilsObject->resetInstanceCache();

        $keepThese = [
            ConfigFile::class,
        ];
        $registryKeys = Registry::getKeys();

        foreach ($registryKeys as $registryKey) {
            if (in_array($registryKey, $keepThese)) {
                continue;
            }
            Registry::set($registryKey, null);
        }
    }
}
