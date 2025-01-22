<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Service;

use OxidEsales\GraphQL\Base\Exception\FingerprintMissingException;
use OxidEsales\GraphQL\Base\Exception\FingerprintValidationException;
use OxidEsales\GraphQL\Base\Service\CookieServiceInterface;
use OxidEsales\GraphQL\Base\Service\FingerprintService;
use OxidEsales\GraphQL\Base\Service\FingerprintServiceInterface;
use PHPUnit\Framework\TestCase;

class FingerprintServiceTest extends TestCase
{
    public function testGetFingerprintGeneratesRandomStrings(): void
    {
        $fingerprintService = $this->getSut();

        $result1 = $fingerprintService->getFingerprint();
        $result2 = $fingerprintService->getFingerprint();

        $this->assertNotSame($result1, $result2);
    }

    public function testGetFingerprintLengthIsAtLeast32(): void
    {
        $fingerprintService = $this->getSut();

        $result = $fingerprintService->getFingerprint();

        $this->assertTrue(strlen($result) >= 32);
    }

    public function testHashFingerprintReturnsNotEmptyResultOnEmptyParameter(): void
    {
        $fingerprintService = $this->getSut();

        $result = $fingerprintService->hashFingerprint('');

        $this->assertNotEmpty($result);
    }

    public function testHashFingerprintReturnsTheSameResultOnSameParameter(): void
    {
        $fingerprintService = $this->getSut();

        $value = uniqid();
        $result1 = $fingerprintService->hashFingerprint($value);
        $result2 = $fingerprintService->hashFingerprint($value);

        $this->assertSame($result1, $result2);
    }

    public function testHashFingerprintReturnsHashedVersionOfFingerprint(): void
    {
        $fingerprintService = $this->getSut();

        $originalFingerprint = $fingerprintService->getFingerprint();
        $hashedFingerprint = $fingerprintService->hashFingerprint($originalFingerprint);

        $this->assertNotSame($originalFingerprint, $hashedFingerprint);
    }

    public function testFingerprintValidationOnCorrectData(): void
    {
        $fingerprintService = $this->getSut(
            cookieService: $this->createConfiguredStub(CookieServiceInterface::class, [
                'getFingerprintCookie' => $cookieValue = uniqid()
            ])
        );

        $hashedFingerprint = $fingerprintService->hashFingerprint($cookieValue);

        $fingerprintService->validateFingerprintHashToCookie($hashedFingerprint);
        $this->addToAssertionCount(1);
    }

    public function testFingerprintValidationOnIncorrectData(): void
    {
        $fingerprintService = $this->getSut(
            cookieService: $this->createConfiguredStub(CookieServiceInterface::class, [
                'getFingerprintCookie' => uniqid()
            ])
        );

        $hashedWrongFingerprint = $fingerprintService->hashFingerprint(uniqid());

        $this->expectException(FingerprintValidationException::class);
        $fingerprintService->validateFingerprintHashToCookie($hashedWrongFingerprint);
    }

    public function testFingerprintValidationDoesNotCatchCookieFingerprintMissingException(): void
    {
        $fingerprintService = $this->getSut(
            cookieService: $cookieServiceMock = $this->createMock(CookieServiceInterface::class)
        );
        $cookieServiceMock->method('getFingerprintCookie')
            ->willThrowException(new FingerprintMissingException(uniqid()));

        $this->expectException(FingerprintMissingException::class);
        $fingerprintService->validateFingerprintHashToCookie(uniqid());
    }

    public function getSut(
        CookieServiceInterface $cookieService = null,
    ): FingerprintServiceInterface {
        return new FingerprintService(
            cookieService: $cookieService ?? $this->createStub(CookieServiceInterface::class)
        );
    }
}
