<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\GraphQL\Base\Exception\MissingSignatureKey;
use OxidEsales\GraphQL\Base\Service\ModuleConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\UnicodeString;

class ModuleConfigurationTest extends TestCase
{
    public function testGenerateSignatureKeyCreatesRandom64BytesKeys(): void
    {
        $moduleSettingBridgeMock = $this->getMockBuilder(ModuleSettingServiceInterface::class)->getMock();
        $moduleConfiguration = new ModuleConfiguration($moduleSettingBridgeMock);
        $iterations = 5;
        $keys = [];

        for ($i = 0; $i < $iterations; $i++) {
            $key = $moduleConfiguration->generateSignatureKey();
            $this->assertGreaterThanOrEqual(
                64,
                strlen($key),
                'Signature key needs to be at least 64 chars, ' . strlen($key) . ' chars given'
            );
            $this->assertTrue(is_string($key), 'Signature key needs to be a string');
            $keys[] = $key;
        }
        array_unique($keys);
        $this->assertEquals(
            $iterations,
            count($keys),
            'All signature keys need to be random'
        );
    }

    public static function shortSignatureKeyProvider(): array
    {
        return [
            [''],
            ['too short'],
        ];
    }

    /**
     * @dataProvider shortSignatureKeyProvider
     *
     * @param string $signature
     */
    public function testGetShortSignatureKey(string $signature): void
    {
        $moduleSettingBridgeMock = $this->getMockBuilder(ModuleSettingServiceInterface::class)->getMock();
        $moduleSettingBridgeMock->method('getString')->willReturn(new UnicodeString($signature));

        $moduleConfiguration = new ModuleConfiguration($moduleSettingBridgeMock);

        $this->expectException(MissingSignatureKey::class);

        $moduleConfiguration->getSignatureKey();
    }

    public function testGetSignatureKey(): void
    {
        $signature = '33189b36e3fe1198cb92f49c8b6701cfd92bc58876f33361fc97bb69614c9592';
        $moduleSettingBridgeMock = $this->getMockBuilder(ModuleSettingServiceInterface::class)->getMock();
        $moduleSettingBridgeMock->method('getString')->willReturn(new UnicodeString($signature));

        $moduleConfiguration = new ModuleConfiguration($moduleSettingBridgeMock);
        $sig = $moduleConfiguration->getSignatureKey();

        $this->assertTrue(is_string($sig));
    }

    public function testGetTokenLifetimeDefault(): void
    {
        $moduleSettingBridgeMock = $this->getMockBuilder(ModuleSettingServiceInterface::class)->getMock();
        $moduleSettingBridgeMock->method('getString')->willReturn(new UnicodeString('asdf'));

        $moduleConfiguration = new ModuleConfiguration($moduleSettingBridgeMock);

        $this->assertSame('+8 hours', $moduleConfiguration->getTokenLifeTime());
    }

    public function testGetTokenLifetime(): void
    {
        $moduleSettingBridgeMock = $this->getMockBuilder(ModuleSettingServiceInterface::class)->getMock();
        $moduleSettingBridgeMock->method('getString')->willReturn(new UnicodeString('24hrs'));

        $moduleConfiguration = new ModuleConfiguration($moduleSettingBridgeMock);

        $this->assertSame('+24 hours', $moduleConfiguration->getTokenLifeTime());
    }

    public function testGetUserTokenQuota(): void
    {
        $moduleSettingBridgeMock = $this->getMockBuilder(ModuleSettingServiceInterface::class)->getMock();
        $moduleSettingBridgeMock->method('getInteger')->willReturn(666);

        $moduleConfiguration = new ModuleConfiguration($moduleSettingBridgeMock);

        $this->assertSame(666, $moduleConfiguration->getUserTokenQuota());
    }

    public function testGetCookieSetting(): void
    {
        $moduleSettingBridgeMock = $this->getMockBuilder(ModuleSettingServiceInterface::class)->getMock();
        $unicodeString = new UnicodeString(ModuleConfiguration::COOKIE_SETTING_SAME);
        $moduleSettingBridgeMock->method('getString')->willReturn($unicodeString);

        $moduleConfiguration = new ModuleConfiguration($moduleSettingBridgeMock);

        $this->assertSame(ModuleConfiguration::COOKIE_SETTING_SAME, $moduleConfiguration->getCookieSetting());
    }
}
