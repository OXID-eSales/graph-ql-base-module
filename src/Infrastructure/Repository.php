<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Infrastructure;

use Doctrine\DBAL\Result;
use InvalidArgumentException;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\GraphQL\Base\DataType\Filter\FilterListInterface as FilterList;
use OxidEsales\GraphQL\Base\DataType\Pagination\Pagination;
use OxidEsales\GraphQL\Base\DataType\ShopModelAwareInterface;
use OxidEsales\GraphQL\Base\DataType\Sorting\Sorting;
use OxidEsales\GraphQL\Base\Exception\NotFound;
use RuntimeException;

class Repository
{
    public function __construct(
        private readonly QueryBuilderFactoryInterface $queryBuilderFactory
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @template T
     *
     * @param class-string<T> $type
     *
     * @throws InvalidArgumentException if $type is not instance of ShopModelAwareInterface
     * @throws NotFound                 if BaseModel can not be loaded
     *
     * @return T
     */
    public function getById(
        string $id,
        string $type,
        bool $disableSubShop = true
    ) {
        $baseModel = $this->getModel($type::getModelClass(), $disableSubShop);

        if (!$baseModel->load($id) || (method_exists($baseModel, 'canView') && !$baseModel->canView())) {
            throw new NotFound($id);
        }
        $type = new $type($baseModel);

        if (!($type instanceof ShopModelAwareInterface)) {
            throw new InvalidArgumentException();
        }

        return $type;
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @template T of ShopModelAwareInterface
     *
     * @param class-string<T> $type
     *
     * @return T[]
     *
     * @throws InvalidArgumentException if model in $type is not instance of BaseModel
     */
    public function getList(
        string $type,
        FilterList $filterList,
        Pagination $pagination,
        Sorting $sorting,
        bool $disableSubShop = true
    ): array {
        $types = [];
        $baseModel = $this->getModel(
            $type::getModelClass(),
            $disableSubShop
        );
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder->select($baseModel->getViewName() . '.*')
            ->from($baseModel->getViewName());

        if (
            $filterList->getActive() !== null &&
            $filterList->getActive()->equals() === true
        ) {
            $activeSnippet = $baseModel->getSqlActiveSnippet();

            if (strlen($activeSnippet)) {
                $queryBuilder->andWhere($activeSnippet);
            }
        }

        $filters = array_filter($filterList->getFilters());
        foreach ($filters as $field => $fieldFilter) {
            $fieldFilter->addToQuery($queryBuilder, $field);
        }

        $pagination->addPaginationToQuery($queryBuilder);

        $sorting->addToQuery($queryBuilder);

        /** @var Result $result */
        $result = $queryBuilder->execute();
        foreach ($result->fetchAllAssociative() as $row) {
            $newModel = clone $baseModel;
            $newModel->assign($row);
            $types[] = new $type($newModel);
        }

        return $types;
    }

    /**
     * @return true
     * @throws NotFound
     */
    public function delete(BaseModel $baseModel): bool
    {
        if (!$baseModel->delete()) {
            throw new RuntimeException('Failed deleting object');
        }

        return true;
    }

    /**
     * @return true
     */
    public function saveModel(BaseModel $baseModel): bool
    {
        if (!$baseModel->save()) {
            throw new RuntimeException('Object save failed');
        }

        return true;
    }

    /**
     * @param class-string $modelClass
     *
     * @throws InvalidArgumentException if model in $type is not instance of BaseModel
     */
    private function getModel(string $modelClass, bool $disableSubShop): BaseModel
    {
        $model = oxNew($modelClass);

        if (!($model instanceof BaseModel)) {
            throw new InvalidArgumentException();
        }

        if (method_exists($model, 'setDisableShopCheck')) {
            $model->setDisableShopCheck($disableSubShop);
        }

        return $model;
    }
}
