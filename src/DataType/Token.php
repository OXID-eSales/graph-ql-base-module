<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\DataType;

use DateTimeInterface;
use OxidEsales\GraphQL\Base\Infrastructure\Model\Token as GraphQLTokenModel;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * @Type()
 */
final class Token implements ShopModelAwareInterface
{
    public function __construct(private readonly GraphQLTokenModel $graphQLTokenModel)
    {
    }

    public function getEshopModel(): GraphQLTokenModel
    {
        return $this->graphQLTokenModel;
    }

    /**
     * Field of Base module's Token-Type.
     * @Field()
     */
    public function createdAt(): ?DateTimeInterface
    {
        return DateTimeImmutableFactory::fromString(
            (string)$this->graphQLTokenModel->getRawFieldData('issued_at')
        );
    }

    /**
     * Field of Base module's Token-Type.
     * @Field()
     */
    public function customerId(): ID
    {
        return new ID((string)$this->graphQLTokenModel->getRawFieldData('oxuserid'));
    }

    /**
     * Field of Base module's Token-Type.
     * @Field()
     */
    public function expiresAt(): ?DateTimeInterface
    {
        return DateTimeImmutableFactory::fromString(
            (string)$this->graphQLTokenModel->getRawFieldData('expires_at')
        );
    }

    /**
     * Field of Base module's Token-Type.
     * @Field()
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function id(): ID
    {
        return new ID((string)$this->graphQLTokenModel->getId());
    }

    /**
     * Field of Base module's Token-Type.
     * @Field()
     */
    public function shopId(): ID
    {
        return new ID((string)$this->graphQLTokenModel->getRawFieldData('oxshopid'));
    }

    /**
     * Field of Base module's Token-Type
     * @Field()
     */
    public function token(): string
    {
        return (string)$this->graphQLTokenModel->getRawFieldData('token');
    }

    /**
     * Field of Base module's Token-Type.
     * @Field()
     */
    public function userAgent(): string
    {
        return $this->graphQLTokenModel->getRawFieldData('useragent');
    }

    public static function getModelClass(): string
    {
        return GraphQLTokenModel::class;
    }
}
