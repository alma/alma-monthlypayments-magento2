<?php

namespace Alma\MonthlyPayments\Helpers;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

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
     * @param array $productIds
     * @return Collection
     */
    public function getProductsItems(array $productIds): Collection
    {
        return $this->productCollection->addAttributeToSelect('*')->addFieldToFilter('entity_id', ['in'=>$productIds]);
    }

    /**
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

    public function getProductsCategories(Collection $products): CategoryCollection
    {
        $categoriesId = $this->getProductsCategoriesIds($products);
        return $this->categoryCollection->addAttributeToSelect(['*'])->addFieldToFilter('entity_id', ['in'=>$categoriesId]);

    }

}
