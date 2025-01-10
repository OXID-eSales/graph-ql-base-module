<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Codeception\Module;

use Codeception\Util\HttpCode;
use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;
use Codeception\Module\REST;
use InvalidArgumentException;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Parser;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\GraphQL\Base\Service\JwtConfigurationBuilder;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\BrowserKit\CookieJar;

class AcceptanceHelper extends Module implements DependsOnModule
{
    /** @var REST */
    private $rest;

    /**
     * @return array|mixed
     */
    public function _depends(): array
    {
        return [REST::class => 'Codeception\Module\REST is required'];
    }

    public function _inject(REST $rest): void
    {
        $this->rest = $rest;
    }

    public function getRest(): REST
    {
        return $this->rest;
    }

    public function sendGQLQuery(
        string $query,
        ?array $variables = null,
        int $language = 0,
        int $shopId = 1,
        array $additionalParameters = []
    ): void {
        $uri = '/graphql?lang=' . $language . '&shp=' . $shopId;

        foreach ($additionalParameters as $key => $value) {
            $uri .= '&' . $key . '=' . $value;
        }

        $this->rest->haveHTTPHeader('Content-Type', 'application/json');
        $this->rest->sendPOST($uri, [
            'query' => $query,
            'variables' => $variables,
        ]);
    }

    public function login(?string $username = null, ?string $password = null, int $shopId = 1): void
    {
        $this->logout();

        $query = 'query ($username: String, $password: String) { token (username: $username, password: $password) }';
        $variables = [
            'username' => $username,
            'password' => $password,
        ];

        $this->sendGQLQuery($query, $variables, 0, $shopId);
        $this->rest->seeResponseCodeIs(HttpCode::OK);
        $this->rest->seeResponseIsJson();
        $this->seeResponseContainsValidJWTToken();

        $this->rest->amBearerAuthenticated($this->grabTokenFromResponse());
    }

    public function logout(): void
    {
        $this->rest->deleteHeader('Authorization');
    }

    public function grabJsonResponseAsArray(): array
    {
        return json_decode($this->rest->grabResponse(), true);
    }

    public function grabTokenFromResponse(): string
    {
        return $this->grabJsonResponseAsArray()['data']['token'];
    }

    public function seeResponseContainsValidJWTToken(): void
    {
        $token = $this->grabTokenFromResponse();

        try {
            (new Parser(new JoseEncoder()))->parse($token);
        } catch (InvalidArgumentException $e) {
            throw new AssertionFailedError(sprintf('Not a valid JWT token: %s', $token));
        }
    }

    public function grabCookies(): CookieJar
    {
        return $this->getModule('PhpBrowser')->client->getCookieJar();
    }

    public function extractSidFromResponseCookies(): string
    {
        $cookieHeaders = $this->rest->grabHttpHeader('Set-Cookie', false);

        $sid = '';

        foreach ($cookieHeaders as $value) {
            preg_match('/^(sid=)([a-z0-9]*);/', $value, $matches);

            if (isset($matches[2])) {
                $sid = $matches[2];

                break;
            }
        }

        return $sid;
    }

    public function parseJwt(string $jwt): Token
    {
        $container = ContainerFactory::getInstance()->getContainer();
        $config = $container->get(JwtConfigurationBuilder::class);

        $token = $config->getConfiguration()->parser()->parse($jwt);

        return $token;
    }
}
