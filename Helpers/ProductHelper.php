<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\MonthlyPayments\Model\Exceptions\AlmaProductException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;

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
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @param Logger $logger
     * @param CollectionFactory $productCollectionFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ProductRepository $productRepository
     */
    public function __construct(
        Logger                    $logger,
        CollectionFactory         $productCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductRepository         $productRepository
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productRepository = $productRepository;
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
        return $collection->addAttributeToFilter('entity_id', ['in' => $productIds]);
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
     * @throws LocalizedException
     */
    public function getProductsCategories(Collection $products): CategoryCollection
    {
        $categoriesId = $this->getProductsCategoriesIds($products);

        return $this->categoryCollectionFactory->create()->addAttributeToSelect('*')->addFieldToFilter('entity_id', ['in' => $categoriesId]);
    }

    /**
     * Disable a product
     *
     * @param Product $product
     * @return void
     * @throws AlmaProductException
     */
    public function disableProduct(ProductInterface $product): void
    {
        $product->setStatus(Status::STATUS_DISABLED);
        try {
            $this->productRepository->save($product);

        } catch (CouldNotSaveException|InputException|StateException $e) {
            $this->logger->warning(
                "Impossible to disable Product",
                ['productId' => $product->getId(), 'message' => $e->getMessage()]
            );
            throw new AlmaProductException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Enable a product
     *
     * @param Product $product
     * @return void
     * @throws AlmaProductException
     */
    public function enableProduct(ProductInterface $product): void
    {
        $product->setStatus(Status::STATUS_ENABLED);
        try {
            $this->productRepository->save($product);

        } catch (CouldNotSaveException|InputException|StateException $e) {
            $this->logger->warning(
                "Impossible to enable Product",
                ['productId' => $product->getId(), 'message' => $e->getMessage()]
            );
            throw new AlmaProductException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
