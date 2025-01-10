<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Event\Subscriber;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\AfterModelUpdateEvent;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\BeforeModelUpdateEvent;
use OxidEsales\GraphQL\Base\Event\Subscriber\PasswordChangeSubscriber;
use OxidEsales\GraphQL\Base\Infrastructure\RefreshTokenRepositoryInterface;
use OxidEsales\GraphQL\Base\Infrastructure\Token;
use OxidEsales\GraphQL\Base\Service\UserModelService;
use OxidEsales\GraphQL\Base\Tests\Unit\BaseTestCase;

class PasswordChangeSubscriberTest extends BaseTestCase
{
    public function testSubscribedEventsConfiguration(): void
    {
        $sut = $this->getSut();
        $configuration = $sut->getSubscribedEvents();

        $this->assertTrue(array_key_exists(BeforeModelUpdateEvent::class, $configuration));
        $this->assertTrue(array_key_exists(AfterModelUpdateEvent::class, $configuration));
        $this->assertTrue($configuration[BeforeModelUpdateEvent::class] === 'handleBeforeUpdate');
        $this->assertTrue($configuration[AfterModelUpdateEvent::class] === 'handleAfterUpdate');
    }

    public function testSubscriberWithUserModelPwdChange(): void
    {
        $userModelService = $this->createPartialMock(UserModelService::class, ['isPasswordChanged']);
        $userModelService->method('isPasswordChanged')->with($userId = uniqid())->willReturn(true);

        $refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->expects($this->once())
            ->method('invalidateUserTokens');

        $tokenInfrastructure = $this->createMock(Token::class);
        $tokenInfrastructure->expects($this->once())
            ->method('invalidateUserTokens')
            ->with($this->equalTo($userId));

        $userModelStub = $this->createConfiguredStub(User::class, ['getId' => $userId]);
        $beforeUpdateStub = $this->createConfiguredStub(BeforeModelUpdateEvent::class, ['getModel' => $userModelStub]);
        $afterUpdateStub = $this->createConfiguredStub(AfterModelUpdateEvent::class, ['getModel' => $userModelStub]);

        $sut = $this->getSut($userModelService, $refreshTokenRepository, $tokenInfrastructure);
        $sut->handleBeforeUpdate($beforeUpdateStub);
        $sut->handleAfterUpdate($afterUpdateStub);
    }

    public function testSubscriberWithMultipleUserModelsPwdChange(): void
    {
        $userModelService = $this->createPartialMock(UserModelService::class, ['isPasswordChanged']);
        $userModelService->method('isPasswordChanged')->willReturn(true);

        $userModelStub1 = $this->createConfiguredStub(User::class, ['getId' => $userId1 = uniqid()]);
        $userModelStub2 = $this->createConfiguredStub(User::class, ['getId' => $userId2 = uniqid()]);

        $methodArgs = [];
        $refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->expects($this->exactly(2))
            ->method('invalidateUserTokens')
            ->willReturnCallback(function ($userId) use (&$methodArgs) {
                $methodArgs[] = $userId;
            });

        $tokenInfrastructure = $this->createMock(Token::class);
        $tokenInfrastructure->expects($this->exactly(2))
            ->method('invalidateUserTokens');

        $beforeUpdateStub1 = $this->createConfiguredStub(
            BeforeModelUpdateEvent::class,
            ['getModel' => $userModelStub1]
        );
        $beforeUpdateStub2 = $this->createConfiguredStub(
            BeforeModelUpdateEvent::class,
            ['getModel' => $userModelStub2]
        );
        $afterUpdateStub1 = $this->createConfiguredStub(
            AfterModelUpdateEvent::class,
            ['getModel' => $userModelStub1]
        );
        $afterUpdateStub2 = $this->createConfiguredStub(
            AfterModelUpdateEvent::class,
            ['getModel' => $userModelStub2]
        );

        $sut = $this->getSut($userModelService, $refreshTokenRepository, $tokenInfrastructure);
        $sut->handleBeforeUpdate($beforeUpdateStub1);
        $sut->handleBeforeUpdate($beforeUpdateStub2);
        $sut->handleAfterUpdate($afterUpdateStub1);
        $sut->handleAfterUpdate($afterUpdateStub2);

        // Ensure that invalidateUserTokens was called with the correct parameters
        $this->assertCount(2, $methodArgs);
        $this->assertSame($userId1, $methodArgs[0]);
        $this->assertSame($userId2, $methodArgs[1]);
    }

    public function testSubscriberWithNoUserModel(): void
    {
        $userModelService = $this->createPartialMock(UserModelService::class, ['isPasswordChanged']);
        $userModelService->expects($this->never())
            ->method('isPasswordChanged');

        $refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->expects($this->never())
            ->method('invalidateUserTokens');

        $tokenInfrastructure = $this->createMock(Token::class);
        $tokenInfrastructure->expects($this->never())
            ->method('invalidateUserTokens');

        $beforeUpdateStub = $this->createConfiguredStub(BeforeModelUpdateEvent::class, ['getModel' => new Article()]);
        $afterUpdateStub = $this->createConfiguredStub(AfterModelUpdateEvent::class, ['getModel' => new Article()]);

        $sut = $this->getSut($userModelService, $refreshTokenRepository, $tokenInfrastructure);
        $sut->handleBeforeUpdate($beforeUpdateStub);
        $sut->handleAfterUpdate($afterUpdateStub);
    }

    public function testSubscriberWithUserModelNoPwdChanged(): void
    {
        $userModelService = $this->createPartialMock(UserModelService::class, ['isPasswordChanged']);
        $userModelService->method('isPasswordChanged')->with($userId = uniqid())->willReturn(false);

        $refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepository->expects($this->never())
            ->method('invalidateUserTokens');

        $tokenInfrastructure = $this->createMock(Token::class);
        $tokenInfrastructure->expects($this->never())
            ->method('invalidateUserTokens');

        $userModelStub = $this->createConfiguredStub(User::class, ['getId' => $userId]);
        $beforeUpdateStub = $this->createConfiguredStub(BeforeModelUpdateEvent::class, ['getModel' => $userModelStub]);
        $afterUpdateStub = $this->createConfiguredStub(AfterModelUpdateEvent::class, ['getModel' => $userModelStub]);

        $sut = $this->getSut($userModelService, $refreshTokenRepository, $tokenInfrastructure);
        $sut->handleBeforeUpdate($beforeUpdateStub);
        $sut->handleAfterUpdate($afterUpdateStub);
    }

    protected function getSut(
        UserModelService $userModelService = null,
        RefreshTokenRepositoryInterface $refreshTokenRepository = null,
        Token $tokenInfrastructure = null,
    ): PasswordChangeSubscriber {
        return new PasswordChangeSubscriber(
            userModelService: $userModelService ?? $this->createStub(UserModelService::class),
            refreshTokenRepository:
                $refreshTokenRepository ?? $this->createStub(RefreshTokenRepositoryInterface::class),
            tokenInfrastructure: $tokenInfrastructure ?? $this->createStub(Token::class)
        );
    }
}
