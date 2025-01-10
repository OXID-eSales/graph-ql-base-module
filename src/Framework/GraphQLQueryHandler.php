<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Framework;

use Closure;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use Psr\Log\LoggerInterface;
use Throwable;

class GraphQLQueryHandler
{
    /** @var Error[] */
    private static array $errors = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly SchemaFactory $schemaFactory,
        private readonly RequestReader $requestReader,
        private readonly ResponseWriter $responseWriter,
        private readonly TimerHandler $timerHandler
    ) {
    }

    public function executeGraphQLQuery(): void
    {
        $result = $this->executeQuery(
            $this->requestReader->getGraphQLRequestData()
        );
        $result->setErrorFormatter($this->getErrorFormatter());
        $this->responseWriter->renderJsonResponse(
            $result->toArray()
        );
    }

    /**
     * Execute the GraphQL query
     *
     * @param array{query: string, variables?: string[], operationName?: string} $queryData
     *
     * @throws Throwable
     */
    private function executeQuery(array $queryData): ExecutionResult
    {
        $graphQL = new GraphQL();
        $variables = null;
        $operationName = null;

        if (isset($queryData['variables'])) {
            $variables = $queryData['variables'];
        }

        if (isset($queryData['operationName'])) {
            $operationName = $queryData['operationName'];
        }

        $schema = $this->schemaFactory->getSchema();

        $queryTimer = $this->timerHandler->create('query-exec')->start();

        $result = $graphQL->executeQuery(
            $schema,
            $queryData['query'],
            null,
            null,
            $variables,
            $operationName
        );

        $result->errors = array_merge(
            $result->errors,
            self::$errors
        );

        $queryTimer->stop();

        return $result;
    }

    /**
     * @deprecated Exceptions should be thrown instead of using this method
     */
    public static function addError(Error $error): void
    {
        self::$errors[] = $error;
    }

    private function getErrorFormatter(): Closure
    {
        return function (Error $error) {
            $this->logger->error($error->getMessage(), [$error]);

            return FormattedError::createFromException($error);
        };
    }
}
