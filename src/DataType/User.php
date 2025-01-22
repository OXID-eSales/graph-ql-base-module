<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\DataType;

use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * @Type()
 */
final class User implements ShopModelAwareInterface, UserInterface
{
    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) TODO: Consider extracting AnonymousUser class
     */
    public function __construct(
        private readonly EshopUserModel $eshopUserModel,
        private readonly bool $isAnonymous = false
    ) {
    }

    public function getEshopModel(): EshopUserModel
    {
        return $this->eshopUserModel;
    }

    /**
     * Field of Base module's User-Type.
     * @Field()
     */
    public function email(): string
    {
        return (string)$this->eshopUserModel->getRawFieldData('oxusername');
    }

    /**
     * Field of Base module's User-Type.
     * @Field()
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function id(): ID
    {
        return new ID((string)$this->eshopUserModel->getId());
    }

    public function isAnonymous(): bool
    {
        return $this->isAnonymous;
    }

    public static function getModelClass(): string
    {
        return EshopUserModel::class;
    }
}
