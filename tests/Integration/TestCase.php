<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Integration;

use DateTimeImmutable;
use Lcobucci\JWT\UnencryptedToken;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\EshopCommunity\Tests\TestContainerFactory;
use OxidEsales\Facts\Facts;
use OxidEsales\GraphQL\Base\DataType\UserInterface;
use OxidEsales\GraphQL\Base\Framework\GraphQLQueryHandler;
use OxidEsales\GraphQL\Base\Framework\RequestReader;
use OxidEsales\GraphQL\Base\Framework\ResponseWriter;
use OxidEsales\GraphQL\Base\Framework\SchemaFactory;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\GraphQL\Base\Infrastructure\Token as TokenInfrastructure;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Base\Service\Authorization;
use OxidEsales\GraphQL\Base\Service\ModuleConfiguration;
use OxidEsales\GraphQL\Base\Service\Token;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class TestCase extends IntegrationTestCase
{
    protected static $queryResult;

    protected static $logResult;

    /** @var ?ContainerBuilder */
    protected static $container;

    protected static $query;

    public function setUp(): void
    {
        parent::setUp();

        $connection = ContainerFactory::getInstance()
            ->getContainer()
            ->get(QueryBuilderFactoryInterface::class)
            ->create()
            ->getConnection();

        $connection->executeStatement(
            file_get_contents(
                __DIR__ . '/../Fixtures/dump.sql'
            )
        );

        \OxidEsales\Eshop\Core\Registry::getLang()->resetBaseLanguage();

        if (static::$container !== null) {
            return;
        }
        $containerFactory = new TestContainerFactory();
        static::$container = $containerFactory->create();

        $responseWriterDefinition = static::$container->getDefinition(ResponseWriter::class);
        $responseWriterDefinition->setClass(ResponseWriterStub::class);

        $requestReaderDefinition = static::$container->getDefinition(RequestReader::class);
        $requestReaderDefinition->setClass(RequestReaderStub::class);

        $legacyServiceDefinition = static::$container->getDefinition(Legacy::class);
        $legacyServiceDefinition->setClass(LegacyStub::class);

        $legacyServiceDefinition = static::$container->getDefinition(ModuleConfiguration::class);
        $legacyServiceDefinition->setClass(ModuleConfigurationStub::class);

        $tokenInfrastructureDefinition = static::$container->getDefinition(TokenInfrastructure::class);
        $tokenInfrastructureDefinition->setClass(TokenInfrastructureStub::class);

        $logger = new LoggerStub();

        static::$container->set(
            LoggerInterface::class,
            $logger
        );
        static::$container->autowire(
            LoggerInterface::class,
            get_class($logger)
        );

        $cache = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
        static::$container->set(
            'oxidesales.graphqlbase.cacheadapter',
            $cache
        );

        static::beforeContainerCompile();

        static::$container->compile();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        static::$queryResult = null;
        static::$logResult = null;
        static::$query = null;

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (static::$container) {
                $this->setAuthToken('');
            }
            unset($_SERVER['HTTP_AUTHORIZATION']);
        }
    }

    protected function setAuthToken(string $token): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        $authToken = static::$container->get(RequestReader::class)->getAuthToken();

        $tokenService = static::$container->get(Token::class);
        $refClass = new ReflectionClass(Token::class);
        $prop = $refClass->getProperty('token');
        $prop->setAccessible(true);
        $prop->setValue($tokenService, $authToken);

        $authentication = static::$container->get(Authentication::class);
        $refClass = new ReflectionClass(Authentication::class);
        $prop = $refClass->getProperty('tokenService');
        $prop->setAccessible(true);
        $prop->setValue($authentication, $tokenService);

        $authorization = static::$container->get(Authorization::class);
        $refClass = new ReflectionClass(Authorization::class);
        $prop = $refClass->getProperty('tokenService');
        $prop->setAccessible(true);
        $prop->setValue($authorization, $tokenService);

        $schema = static::$container->get(SchemaFactory::class);
        $refClass = new ReflectionClass(SchemaFactory::class);
        $prop = $refClass->getProperty('schema');
        $prop->setAccessible(true);
        $prop->setValue($schema, null);
    }

    protected function query(string $query, ?array $variables = null, ?string $operationName = null): array
    {
        static::$query = [
            'query' => $query,
            'variables' => $variables,
            'operationName' => $operationName,
        ];
        static::$container->get(GraphQLQueryHandler::class)
            ->executeGraphQLQuery();

        return static::$queryResult;
    }

    protected function setGETRequestParameter(string $name, string $value): void
    {
        $_GET[$name] = $value;
    }

    protected function uploadFile(
        string $fileName,
        array $mutationData,
        ?string $token = null
    ): array {
        $variables = $mutationData['variables'];
        $mutation = $mutationData['mutation'];

        $fields = [
            'operation' => $mutationData['name'],
            'operations' => [
                'query' => $mutation,
                'variables' => $variables,
            ],
        ];
        $map = [
            'map' => [
                '0' => ['variables.file'],
            ],
        ];

        $files = [
            $fileName => $fileName,
        ];

        $boundary = '-------------' . uniqid();
        $postData = $this->buildFileUpload($boundary, $fields, $map, $files);

        $facts = new Facts();
        $ch = curl_init($facts->getShopUrl() . '/graphql?lang=0&shp=1');

        $headers = [
            'Connection: keep-alive',
            'Pragma: no-cache',
            'Cache-Control: no-cache',
            'Content-Type: multipart/form-data; boundary=' . $boundary,
        ];

        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?: [];
    }

    protected function buildFileUpload(string $delimiter, array $fields, array $map, array $files = []): string
    {
        $data = '';
        $eol = "\r\n";

        foreach (array_merge($fields, $map) as $name => $content) {
            $data .= '--' . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . '"' . $eol . $eol
                . json_encode($content) . $eol;
        }

        $index = 0;

        foreach ($files as $name => $path) {
            $data .= '--' . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $index . '"; filename="' . $name . '"' . $eol
                . 'Content-Type: text/plain' . $eol
                . 'Content-Transfer-Encoding: binary' . $eol;

            $data .= $eol;
            $data .= file_get_contents($path) . $eol;
            $index++;
        }
        $data .= '--' . $delimiter . '--' . $eol;

        return $data;
    }

    public static function responseCallback($body): void
    {
        static::$queryResult = [
            'body' => $body,
        ];
    }

    public static function loggerCallback(string $message): void
    {
        static::$logResult .= $message;
    }

    public static function getGraphQLRequestData(): array
    {
        if (static::$query === null) {
            return [];
        }

        return static::$query;
    }

    protected static function beforeContainerCompile(): void
    {
    }
}

// phpcs:disable

class ResponseWriterStub extends ResponseWriter
{
    public function renderJsonResponse(array $result): void
    {
        TestCase::responseCallback($result);
    }
}

class LegacyStub extends Legacy
{
    public function __construct()
    {
    }

    public function getUserGroupIds(?string $userId): array
    {
        if ('oxdefaultadmin' == $userId) {
            return ['oxidadmin'];
        }

        return parent::getUserGroupIds($userId);
    }

    public function getShopId(): int
    {
        return 1;
    }
}

class TokenInfrastructureStub extends TokenInfrastructure
{
    public function __construct()
    {
    }

    public function canIssueToken(UserInterface $user, int $quota): bool
    {
        return true;
    }

    public function isTokenRegistered(string $tokenId): bool
    {
        return true;
    }

    public function isTokenExpired(string $tokenId): bool
    {
        return false;
    }

    public function registerToken(UnencryptedToken $token, DateTimeImmutable $time, DateTimeImmutable $expire): void
    {
    }

    public function removeExpiredTokens(UserInterface $user): void
    {
    }
}

class RequestReaderStub extends RequestReader
{
    public function getGraphQLRequestData(string $inputFile = 'php://input'): array
    {
        return TestCase::getGraphQLRequestData();
    }
}

class LoggerStub extends \Psr\Log\AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        TestCase::loggerCallback($message);
    }
}

class ModuleConfigurationStub extends \OxidEsales\GraphQL\Base\Service\ModuleConfiguration
{
    public function __construct()
    {
    }

    /**
     * @throws MissingSignatureKey
     */
    public function getSignatureKey(): string
    {
        return '5wi3e0INwNhKe3kqvlH0m4FHYMo6hKef3SzweEjZ8EiPV7I2AC6ASZMpkCaVDTVRg2jbb52aUUXafxXI9/7Cgg==';
    }

    public function getTokenLifeTime(): string
    {
        return '+8 hours';
    }

    public function getUserTokenQuota(): int
    {
        return 1000;
    }

    public function getCookieSetting(): string
    {
        return static::COOKIE_SETTING_SAME;
    }
}
