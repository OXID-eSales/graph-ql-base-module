<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Event\Subscriber;

use OxidEsales\GraphQL\Base\Event\BeforeTokenCreation;
use OxidEsales\GraphQL\Base\Service\CookieServiceInterface;
use OxidEsales\GraphQL\Base\Service\FingerprintServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BeforeTokenCreationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FingerprintServiceInterface $fingerprintService,
        private CookieServiceInterface $cookieService,
    ) {
    }

    public function handle(BeforeTokenCreation $beforeTokenCreation): BeforeTokenCreation
    {
        $builder = $beforeTokenCreation->getBuilder();
        $fingerprint = $this->fingerprintService->getFingerprint();

        $builder->withClaim(
            name: FingerprintServiceInterface::TOKEN_KEY,
            value: $this->fingerprintService->hashFingerprint($fingerprint)
        );

        $this->cookieService->setFingerprintCookie($fingerprint);

        return $beforeTokenCreation;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeTokenCreation::class => 'handle',
        ];
    }
}
