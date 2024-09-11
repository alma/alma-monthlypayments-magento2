<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\ProductHelper;
use Alma\MonthlyPayments\Model\Exceptions\AlmaProductException;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

class ProductHelperTest extends TestCase
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollection;
    /**
     * @var ProductRepository
     */
    private $productRepository;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->productCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->categoryCollection = $this->createMock(CategoryCollectionFactory::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
    }

    private function createProductHelper(): ProductHelper
    {
        return new ProductHelper(...$this->getDependency());
    }

    private function getDependency(): array
    {
        return [
            $this->logger,
            $this->productCollectionFactory,
            $this->categoryCollection,
            $this->productRepository
        ];
    }

    public function testGetProductsCategoriesIds(): void
    {
        $product1 = $this->createMock(Product::class);
        $product1->method('getCategoryIds')->willReturn(["2", "3"]);

        $product2 = $this->createMock(Product::class);
        $product2->method('getCategoryIds')->willReturn(["3", "5"]);

        $iterator = new \ArrayIterator([$product1, $product2]);
        $productCollectionMock = $this->createMock(Collection::class);
        $productCollectionMock->method('getIterator')->willReturn($iterator);
        $result = $this->createProductHelper()->getProductsCategoriesIds($productCollectionMock);
        $this->assertEquals(["2", "3", "5"], $result);
    }

    public function testDisableProductCallSetStatusAndNotThrowExceptionForSave(): void
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->once())->method('setStatus')->with(Status::STATUS_DISABLED);
        $this->productRepository->expects($this->once())->method('save');
        $this->createProductHelper()->disableProduct($product);
    }

    /**
     * @dataProvider errorDataProvider
     */
    public function testDisableProductCallSetStatusAndThrowExceptionForSave($exceptionClass): void
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->once())->method('setStatus')->with(Status::STATUS_DISABLED);
        $this->productRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException($exceptionClass);
        $this->expectException(AlmaProductException::class);
        $this->createProductHelper()->disableProduct($product);
    }

    public function testEnableProductCallSetStatusAndNotThrowExceptionForSave(): void
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->once())->method('setStatus')->with(Status::STATUS_ENABLED);
        $this->productRepository->expects($this->once())->method('save');
        $this->createProductHelper()->enableProduct($product);
    }
    /**
     * @dataProvider errorDataProvider
     */
    public function testEnableProductCallSetStatusAndThrowExceptionForSave($exceptionClass): void
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->once())->method('setStatus')->with(Status::STATUS_ENABLED);
        $this->productRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException($exceptionClass);
        $this->expectException(AlmaProductException::class);
        $this->createProductHelper()->enableProduct($product);
    }

    private function errorDataProvider(): array
    {
        return [
            'Could not save Exception' => [new CouldNotSaveException(new Phrase('Error could not save'))],
            'Input Exception' => [new InputException(new Phrase('Error input exception'))],
            'State Exception' => [new StateException(new Phrase('Error state exception'))],
        ];
    }
}
