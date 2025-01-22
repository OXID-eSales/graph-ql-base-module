<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Event\Subscriber;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\AfterModelUpdateEvent;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\BeforeModelUpdateEvent;
use OxidEsales\GraphQL\Base\Infrastructure\RefreshTokenRepositoryInterface;
use OxidEsales\GraphQL\Base\Infrastructure\Token;
use OxidEsales\GraphQL\Base\Service\UserModelService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PasswordChangeSubscriber implements EventSubscriberInterface
{
    /**
     * Whether the password had been changed.
     *
     * @var array
     */
    protected array $userIdWithChangedPwd = [];

    public function __construct(
        private readonly UserModelService $userModelService,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly Token $token
    ) {
    }

    /**
     * Handle ApplicationModelChangedEvent.
     *
     * @param Event $event Event to be handled
     */
    public function handleBeforeUpdate(Event $event): void
    {
        /** @phpstan-ignore-next-line method.notFound */
        $model = $event->getModel();

        if (!$model instanceof User) {
            return;
        }

        $newPassword = $model->getFieldData('oxpassword');
        if (!$this->userModelService->isPasswordChanged($model->getId(), $newPassword)) {
            return;
        }

        $this->userIdWithChangedPwd[$model->getId()] = true;
    }

    /**
     * Handle ApplicationModelChangedEvent.
     *
     * @param Event $event Event to be handled
     */
    public function handleAfterUpdate(Event $event): void
    {
        /** @phpstan-ignore-next-line method.notFound */
        $model = $event->getModel();

        if (!$model instanceof User || !isset($this->userIdWithChangedPwd[$model->getId()])) {
            return;
        }

        $this->refreshTokenRepository->invalidateUserTokens($model->getId());
        $this->token->invalidateUserTokens($model->getId());
        unset($this->userIdWithChangedPwd[$model->getId()]);
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array<class-string,string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeModelUpdateEvent::class => 'handleBeforeUpdate',
            AfterModelUpdateEvent::class => 'handleAfterUpdate'
        ];
    }
}
