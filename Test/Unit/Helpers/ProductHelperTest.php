<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\ProductHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
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

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->productCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->categoryCollection = $this->createMock(CategoryCollectionFactory::class);
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
            $this->categoryCollection
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
}
