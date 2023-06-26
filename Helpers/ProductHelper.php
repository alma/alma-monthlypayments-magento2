<?php

namespace Alma\MonthlyPayments\Helpers;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class ProductHelper
{
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var CategoryCollection
     */
    private $categoryCollectionFactory;

    /**
     * @param Logger $logger
     * @param CollectionFactory $productCollectionFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        Logger             $logger,
        CollectionFactory  $productCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Get a product collection with product ID array
     *
     * @param array $productIds
     * @return Collection
     */
    public function getProductsItems(array $productIds): Collection
    {
        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        return $collection->addAttributeToFilter('entity_id', ['in'=>$productIds]);
    }

    /**
     * Get all product categories id for a product collection
     *
     * @param Collection $products
     * @return array
     */
    public function getProductsCategoriesIds(Collection $products): array
    {
        $categoriesId = [];
        /** @var Product $product */
        foreach ($products as $product) {
            $categoriesId = array_unique(array_merge($categoriesId, $product->getCategoryIds()));
        }

        return array_values($categoriesId);
    }
    /**
     * Generate categories collection with product a collection
     *
     * @param Collection $products
     * @return CategoryCollection
     */
    public function getProductsCategories(Collection $products): CategoryCollection
    {
        $categoriesId = $this->getProductsCategoriesIds($products);

        return $this->categoryCollectionFactory->create()->addAttributeToSelect('*')->addFieldToFilter('entity_id', ['in'=>$categoriesId]);
    }
}
