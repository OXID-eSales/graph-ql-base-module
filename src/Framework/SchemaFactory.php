<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Framework;

use Mouf\Composer\ClassNameMapper;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Base\Service\Authorization;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TheCodingMachine\GraphQLite\Schema;
use TheCodingMachine\GraphQLite\SchemaFactory as GraphQLiteSchemaFactory;

class SchemaFactory
{
    private ?Schema $schema = null;

    /** @var NamespaceMapperInterface[] */
    private array $namespaceMappers;

    private CacheInterface $cache;

    /**
     * @param NamespaceMapperInterface[] $namespaceMappers
     */
    public function __construct(
        iterable $namespaceMappers,
        private readonly Authentication $authentication,
        private readonly Authorization $authorization,
        private readonly ContainerInterface $container,
        private readonly TimerHandler $timerHandler,
        ?CacheInterface $cache = null
    ) {
        foreach ($namespaceMappers as $namespaceMapper) {
            $this->namespaceMappers[] = $namespaceMapper;
        }
        $this->cache = $cache ?? new Psr16Cache(new NullAdapter());
    }

    public function getSchema(): Schema
    {
        if (null !== $this->schema) {
            return $this->schema;
        }

        $queryTimer = $this->timerHandler
            ->create('schema-gen')
            ->start();

        $schemaFactory = new GraphQLiteSchemaFactory(
            $this->cache,
            $this->container
        );

        $classNameMapper = new ClassNameMapper();

        foreach ($this->namespaceMappers as $namespaceMapper) {
            foreach ($namespaceMapper->getControllerNamespaceMapping() as $namespace => $path) {
                $classNameMapper->registerPsr4Namespace(
                    $namespace,
                    $path
                );
                $schemaFactory->addControllerNameSpace($namespace);
            }

            foreach ($namespaceMapper->getTypeNamespaceMapping() as $namespace => $path) {
                $classNameMapper->registerPsr4Namespace(
                    $namespace,
                    $path
                );
                $schemaFactory->addTypeNameSpace($namespace);
            }
        }

        $schemaFactory->setClassNameMapper($classNameMapper);

        $schemaFactory->setAuthenticationService($this->authentication)
            ->setAuthorizationService($this->authorization);

        $this->schema = $schemaFactory->createSchema();
        $queryTimer->stop();

        return $this->schema;
    }
}
