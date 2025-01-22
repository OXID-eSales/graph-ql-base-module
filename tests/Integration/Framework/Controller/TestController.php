<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Integration\Framework\Controller;

use Exception;
use GraphQL\Error\Error;
use OxidEsales\GraphQL\Base\Exception\InvalidToken;
use OxidEsales\GraphQL\Base\Exception\NotFound;
use OxidEsales\GraphQL\Base\Framework\GraphQLQueryHandler;
use OxidEsales\GraphQL\Base\Tests\Integration\Framework\DataType\TestFilter;
use OxidEsales\GraphQL\Base\Tests\Integration\Framework\DataType\TestSorting;
use Psr\Http\Message\UploadedFileInterface;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Right;

class TestController
{
    /**
     * @Query
     */
    public function testQuery(string $foo): string
    {
        return $foo;
    }

    /**
     * @Query
     * @Logged
     */
    public function testLoggedQuery(string $foo): string
    {
        return $foo;
    }

    /**
     * @Query
     * @Logged
     * @Right("FOOBAR")
     */
    public function testLoggedRightQuery(string $foo): string
    {
        return $foo;
    }

    /**
     * @Query
     * @Logged
     * @Right("BARFOO")
     */
    public function testLoggedButNoRightQuery(string $foo): string
    {
        return $foo;
    }

    /**
     * @Query
     * @Right("FOOBARBAZ")
     */
    public function testOnlyRightQuery(string $foo): string
    {
        return $foo;
    }

    /**
     * @Query
     */
    public function exceptionQuery(string $foo): string
    {
        throw new Exception();
    }

    /**
     * @Query
     */
    public function clientAwareExceptionQuery(string $foo): string
    {
        throw new InvalidToken('invalid token message');
    }

    /**
     * @Query
     */
    public function notFoundExceptionQuery(string $foo): string
    {
        throw new NotFound('Foo does not exist');
    }

    /**
     * @Query
     */
    public function basicInputFilterQuery(TestFilter $testFilter): string
    {
        return (string)$testFilter;
    }

    /**
     * @Query
     */
    public function basicSortingQuery(?TestSorting $testSorting = null): bool
    {
        return true;
    }

    /**
     * @Query
     */
    public function resultWithError(): bool
    {
        GraphQLQueryHandler::addError(
            new Error(
                'error message'
            )
        );

        return true;
    }

    /**
     * @Mutation
     */
    public function uploadedFileContent(UploadedFileInterface $uploadedFile): string
    {
        return file_get_contents($uploadedFile->getStream()->getMetadata('uri'));
    }
}
