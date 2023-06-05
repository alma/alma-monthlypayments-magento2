<?php

namespace Alma\MonthlyPayments\Helpers;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Exception\LocalizedException;

class ProductHelper
{
    /**
     * @var Collection
     */
    private $productCollection;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var CategoryCollection
     */
    private $categoryCollection;

    /**
     * @param Logger $logger
     * @param Collection $productCollection
     * @param CategoryCollection $categoryCollection
     */
    public function __construct(
        Logger $logger,
        Collection $productCollection,
        CategoryCollection $categoryCollection
    ) {
        $this->productCollection = $productCollection;
        $this->logger = $logger;
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * Get a product collection with product ID array
     *
     * @param array $productIds
     * @return Collection
     */
    public function getProductsItems(array $productIds): Collection
    {
        return $this->productCollection->addAttributeToSelect('*')->addFieldToFilter('entity_id', ['in'=>$productIds]);
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
     * Get a collection of categories with categories' ID array
     *
     * @param Collection $products
     * @return CategoryCollection
     * @throws LocalizedException
     */
    public function getProductsCategories(Collection $products): CategoryCollection
    {
        $categoriesId = $this->getProductsCategoriesIds($products);
        return $this->categoryCollection->addAttributeToSelect(['*'])->addFieldToFilter('entity_id', ['in'=>$categoriesId]);

    }

}
