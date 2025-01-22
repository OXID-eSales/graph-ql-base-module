<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Integration\Infrastructure;

use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\GraphQL\Base\DataType\Filter\BoolFilter;
use OxidEsales\GraphQL\Base\DataType\Pagination\Pagination;
use OxidEsales\GraphQL\Base\DataType\Sorting\Sorting as BaseSorting;
use OxidEsales\GraphQL\Base\Infrastructure\Repository;
use PHPUnit\Framework\TestCase;

/**
 * @covers OxidEsales\GraphQL\Base\Infrastructure\Repository
 */
final class RepositoryTest extends TestCase
{
    public function testFatalErrorOnWrongClassById(): void
    {
        $this->expectException(\Error::class);
        $infrastructureRepository = new Repository(
            $this->createMock(QueryBuilderFactoryInterface::class)
        );
        $infrastructureRepository->getById(
            'foo',
            \stdClass::class
        );
    }

    public function testExceptionOnWrongModelById(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $infrastructureRepository = new Repository(
            $this->createMock(QueryBuilderFactoryInterface::class)
        );
        $infrastructureRepository->getById(
            'foo',
            WrongType::class
        );
    }

    public function testExceptionOnWrongTypeById(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $infrastructureRepository = new Repository(
            $this->createMock(QueryBuilderFactoryInterface::class)
        );
        $infrastructureRepository->getById(
            'foo',
            AlsoWrongType::class
        );
    }

    public function testExceptionOnWrongModelByFilter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $infrastructureRepository = new Repository(
            $this->createMock(QueryBuilderFactoryInterface::class)
        );
        $infrastructureRepository->getList(
            WrongType::class,
            new EmptyFilterList(),
            new Pagination(),
            new Sorting([])
        );
    }

    public function testExceptionOnFailToDeleteModel(): void
    {
        $this->expectException(\RuntimeException::class);
        $infrastructureRepository = new Repository(
            $this->createMock(QueryBuilderFactoryInterface::class)
        );
        $infrastructureRepository->delete(new CorrectModel());
    }

    public function testModelSave(): void
    {
        $infrastructureRepository = new Repository(
            $this->createMock(QueryBuilderFactoryInterface::class)
        );

        $model = $this->createPartialMock(
            \OxidEsales\Eshop\Core\Model\BaseModel::class,
            ['save']
        );
        $model->expects($this->any())->method('save')->willReturn('someid');
        $this->assertTrue($infrastructureRepository->saveModel($model));
    }

    public function testModelSaveFailed(): void
    {
        $this->expectException(\RuntimeException::class);
        $infrastructureRepository = new Repository(
            $this->createMock(QueryBuilderFactoryInterface::class)
        );

        $model = $this->createPartialMock(
            \OxidEsales\Eshop\Core\Model\BaseModel::class,
            ['save']
        );
        $model->expects($this->any())->method('save')->willReturn(false);
        $this->assertTrue($infrastructureRepository->saveModel($model));
    }
}

// phpcs:disable

final class Sorting extends BaseSorting
{
}

final class EmptyFilterList implements \OxidEsales\GraphQL\Base\DataType\Filter\FilterListInterface
{
    public function getFilters(): array
    {
        return [];
    }

    public function withActiveFilter(?BoolFilter $boolFilter): self
    {
        return $this;
    }

    public function getActive(): ?BoolFilter
    {
        return null;
    }
}

final class WrongModel
{
}

final class WrongType
{
    public static function getModelClass(): string
    {
        return WrongModel::class;
    }
}

final class CorrectModel extends \OxidEsales\Eshop\Core\Model\BaseModel
{
    public function __construct()
    {
    }

    public function load($oxid)
    {
        return true;
    }

    public function delete($oxid = null)
    {
        return false;
    }
}

final class AlsoWrongType
{
    public static function getModelClass(): string
    {
        return CorrectModel::class;
    }
}

namespace OxidEsales\GraphQL\Base\Infrastructure;

if (!function_exists("\oxNew")) {
    function oxNew(string $class)
    {
        return new $class();
    }
}
