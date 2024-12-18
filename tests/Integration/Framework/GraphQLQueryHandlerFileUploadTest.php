<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Integration\Framework;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Container\BootstrapContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Dao\ProjectYamlDao;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Service\ProjectYamlImportService;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\GraphQL\Base\Tests\Integration\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class GraphQLQueryHandlerFileUploadTest extends TestCase
{
    private const UPLOAD_FILE = __DIR__ . '/../../Fixtures/upload_file.txt';

    private const ADDITIONAL_SERVICES_YAML_PATH = __DIR__;

    public static function setUpBeforeClass(): void
    {
        self::getYamlImportService()->addImport(self::ADDITIONAL_SERVICES_YAML_PATH);
    }

    public static function tearDownAfterClass(): void
    {
        self::getYamlImportService()->removeImport(self::ADDITIONAL_SERVICES_YAML_PATH);
    }

    public function testFileUpload(): void
    {
        ContainerFactory::resetContainer();

        $expected = file_get_contents(self::UPLOAD_FILE);
        $result = $this->uploadFile(self::UPLOAD_FILE, $this->getMutationData());

        $this->assertSame($expected, $result['data']['uploadedFileContent']);
    }

    protected function getMutationData(): array
    {
        return [
            'mutation' => 'mutation upload ($file: Upload!){
                  uploadedFileContent(file: $file)
               }',
            'name' => 'uploadedFileContent',
            'variables' => [
                'file' => null,
            ],
        ];
    }

    private static function getYamlImportService(): ProjectYamlImportService
    {
        $basicContext = BootstrapContainerFactory::getBootstrapContainer()->get(BasicContextInterface::class);
        $projectYamlDao = new ProjectYamlDao($basicContext, new Filesystem());

        return new ProjectYamlImportService($projectYamlDao, $basicContext);
    }
}
