<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Event\Subscriber;

use Lcobucci\JWT\Builder;
use OxidEsales\GraphQL\Base\Event\BeforeTokenCreation;
use OxidEsales\GraphQL\Base\Event\Subscriber\BeforeTokenCreationSubscriber;
use OxidEsales\GraphQL\Base\Service\CookieServiceInterface;
use OxidEsales\GraphQL\Base\Service\FingerprintServiceInterface;
use PHPUnit\Framework\TestCase;

class BeforeTokenCreationSubscriberTest extends TestCase
{
    public function testSubscribedEventsConfiguration(): void
    {
        $beforeTokenCreationSubscriber = $this->getSut();
        $configuration = $beforeTokenCreationSubscriber->getSubscribedEvents();

        $this->assertTrue(array_key_exists(BeforeTokenCreation::class, $configuration));
        $this->assertTrue($configuration[BeforeTokenCreation::class] === 'handle');
    }

    public function testHandleReturnsOriginalEvent(): void
    {
        $beforeTokenCreationSubscriber = $this->getSut();

        $eventStub = $this->createStub(BeforeTokenCreation::class);
        $this->assertSame($eventStub, $beforeTokenCreationSubscriber->handle($eventStub));
    }

    public function testHandleConfiguresHookedJwtBuilderWithFingerprintHashClaim(): void
    {
        $beforeTokenCreationSubscriber = $this->getSut(
            fingerprintService: $fingerprintServiceMock = $this->createMock(FingerprintServiceInterface::class)
        );

        $exampleFingerprint = uniqid();
        $exampleFingerprintHash = uniqid();
        $fingerprintServiceMock->method('getFingerprint')
            ->willReturn($exampleFingerprint);
        $fingerprintServiceMock->method('hashFingerprint')
            ->with($exampleFingerprint)->willReturn($exampleFingerprintHash);

        $eventMock = $this->createMock(BeforeTokenCreation::class);
        $eventMock->method('getBuilder')
            ->willReturn($jwtConfigBuilderSpy = $this->createMock(Builder::class));
        $jwtConfigBuilderSpy->expects($this->once())->method('withClaim')
            ->with(FingerprintServiceInterface::TOKEN_KEY, $exampleFingerprintHash);

        $beforeTokenCreationSubscriber->handle($eventMock);
    }

    public function testHandleTriggersFingerprintCookieSetup(): void
    {
        $beforeTokenCreationSubscriber = $this->getSut(
            fingerprintService: $fingerprintServiceMock = $this->createMock(FingerprintServiceInterface::class),
            cookieService: $cookieServiceSpy = $this->createMock(CookieServiceInterface::class),
        );

        $exampleFingerprint = uniqid();
        $exampleFingerprintHash = uniqid();
        $fingerprintServiceMock->method('getFingerprint')
            ->willReturn($exampleFingerprint);
        $fingerprintServiceMock->method('hashFingerprint')
            ->with($exampleFingerprint)->willReturn($exampleFingerprintHash);

        $cookieServiceSpy->expects($this->once())->method('setFingerprintCookie')->with($exampleFingerprint);

        $eventStub = $this->createStub(BeforeTokenCreation::class);
        $beforeTokenCreationSubscriber->handle($eventStub);
    }

    public function getSut(
        FingerprintServiceInterface $fingerprintService = null,
        CookieServiceInterface $cookieService = null,
    ): BeforeTokenCreationSubscriber {
        return new BeforeTokenCreationSubscriber(
            fingerprintService: $fingerprintService ?? $this->createStub(FingerprintServiceInterface::class),
            cookieService: $cookieService ?? $this->createStub(CookieServiceInterface::class)
        );
    }
}
