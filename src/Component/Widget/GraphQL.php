<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Component\Widget;

use GraphQL\Error\FormattedError;
use OxidEsales\Eshop\Application\Component\Widget\WidgetController;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\GraphQL\Base\Exception\Error;
use OxidEsales\GraphQL\Base\Exception\ErrorCategories;
use OxidEsales\GraphQL\Base\Exception\InvalidRequest;
use OxidEsales\GraphQL\Base\Framework\GraphQLQueryHandler;
use OxidEsales\GraphQL\Base\Framework\TimerHandler;
use OxidEsales\GraphQL\Base\Service\Authentication as GraphQLAuthenticationService;
use Throwable;

/**
 * Implements the GraphQL widget for the OXID eShop to make all
 * of this callable via a SEO Url or via widget.php?cl=graphql
 */
class GraphQL extends WidgetController
{
    public const SESSION_ERROR_MESSAGE = 'OXID eShop PHP session spotted. Ensure you have skipSession=1 '
    . 'parameter sent to the widget.php. For more information about the problem, check '
    . 'Troubleshooting section in documentation.';

    public function init(): void
    {
        /** @var TimerHandler $timerHandler */
        $timerHandler = ContainerFacade::get(TimerHandler::class);
        $timerHandler->create('bootstrap')->startAt($_SERVER['REQUEST_TIME_FLOAT'])->stop();

        try {
            $this->handleShopSession();
            ContainerFacade::get(GraphQLQueryHandler::class)->executeGraphQLQuery();
        } catch (Error $e) {
            $message = FormattedError::createFromException($e);
            if ($this->isAuthenticated($e)) {
                self::sendErrorResponse($message, 200);
            }
            self::sendUnauthenticatedErrorResponse($message, 200);
        } catch (Throwable $e) {
            EshopRegistry::getLogger()->error($e->getMessage(), [$e]);
            self::sendErrorResponse(FormattedError::createFromException($e), 500);
        }
    }

    private function handleShopSession(): void
    {
        //if there's already a php session running, bail out to prevent inconsistent behaviour
        if (PHP_SESSION_NONE !== session_status()) {
            throw new InvalidRequest(self::SESSION_ERROR_MESSAGE);
        }

        $this->setShopUserFromToken();
    }

    private function setShopUserFromToken(): void
    {
        $session = EshopRegistry::getSession();
        $session->setUser(null);
        $session->setBasket(null);
        $session->setVariable('usr', null);

        $authentication = ContainerFacade::get(GraphQLAuthenticationService::class);
        $userId = $authentication->getUser()->id()->val();

        if ($userId) {
            $session->setVariable('usr', $userId);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public static function sendErrorResponse(array $message, int $status): never
    {
        $body = ['errors' => [$message]];

        header('Content-Type: application/json', true, $status);

        print json_encode($body);
        exit;
    }

    public static function sendUnauthenticatedErrorResponse(array $message, int $status): never
    {
        header('WWW-Authenticate: Bearer', true, $status);
        self::sendErrorResponse($message, $status);
    }

    private function isAuthenticated(Error $error): bool
    {
        return $error->getCategory() !== ErrorCategories::PERMISSIONERRORS;
    }
}
