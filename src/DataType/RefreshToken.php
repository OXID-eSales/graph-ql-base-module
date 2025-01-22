<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\DataType;

use DateTimeInterface;
use OxidEsales\GraphQL\Base\Infrastructure\Model\RefreshToken as GraphQLTokenModel;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * @Type()
 */
final class RefreshToken implements ShopModelAwareInterface, RefreshTokenInterface
{
    /** @var GraphQLTokenModel */
    private $graphQLTokenModel;

    public function __construct(GraphQLTokenModel $graphQLTokenModel)
    {
        $this->graphQLTokenModel = $graphQLTokenModel;
    }

    public function getEshopModel(): GraphQLTokenModel
    {
        return $this->graphQLTokenModel;
    }

    /**
     * @Field()
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function id(): ID
    {
        return new ID((string)$this->graphQLTokenModel->getId());
    }

    /**
     * @Field()
     */
    public function token(): string
    {
        return (string)$this->graphQLTokenModel->getRawFieldData('token');
    }

    /**
     * @Field()
     */
    public function createdAt(): ?DateTimeInterface
    {
        return DateTimeImmutableFactory::fromString(
            (string)$this->graphQLTokenModel->getRawFieldData('issued_at')
        );
    }

    /**
     * @Field()
     */
    public function expiresAt(): ?DateTimeInterface
    {
        return DateTimeImmutableFactory::fromString(
            (string)$this->graphQLTokenModel->getRawFieldData('expires_at')
        );
    }

    /**
     * @Field()
     */
    public function customerId(): ID
    {
        return new ID((string)$this->graphQLTokenModel->getRawFieldData('oxuserid'));
    }

    /**
     * @Field()
     */
    public function shopId(): ID
    {
        return new ID((string)$this->graphQLTokenModel->getRawFieldData('oxshopid'));
    }

    public static function getModelClass(): string
    {
        return GraphQLTokenModel::class;
    }
}
