<?php

namespace Alma\MonthlyPayments\Helpers;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class OrderHelper extends AbstractHelper
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ProductHelper
     */
    private $productHelper;
    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param CollectionFactory $orderCollectionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderManagementInterface $orderManagement
     * @param ProductHelper $productHelper
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     */
    public function __construct(
        Context                  $context,
        OrderFactory             $orderFactory,
        CollectionFactory        $orderCollectionFactory,
        OrderRepositoryInterface $orderRepository,
        OrderManagementInterface $orderManagement,
        ProductHelper            $productHelper,
        StoreManagerInterface    $storeManager,
        Logger                   $logger
    )
    {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->orderManagement = $orderManagement;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->productHelper = $productHelper;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Load a specified order.
     * @param string $orderId
     *
     * @return OrderInterface
     */
    public function getOrder(string $orderId): OrderInterface
    {
        $orderModel = $this->orderFactory->create();
        return $orderModel->loadByIncrementId($orderId);
    }

    /**
     * Cancels a specified order.
     * @param string $orderId
     *
     * @return bool
     */
    public function cancel(string $orderId): bool
    {
        return $this->orderManagement->cancel($orderId);
    }

    /**
     * Emails a user a specified order.
     * @param string $orderId
     *
     * @return void
     */
    public function notify(string $orderId): void
    {
        $this->orderManagement->notify($orderId);
    }

    /**
     * Performs persist operations for a specified order.
     *
     * @param Order $order
     *
     * @return void
     */
    public function save(Order $order): void
    {
        $this->orderRepository->save($order);
    }

    /**
     * Get an order collection
     *
     * @param int $customerId
     * @return Collection
     */
    public function getValidOrderCollectionByCustomerId(int $customerId): Collection
    {
        return $this->orderCollectionFactory->create($customerId)
            ->addFieldToSelect('*')
            ->addFieldToFilter('status', ['in' => [Order::STATE_COMPLETE, Order::STATE_PROCESSING]])
            ->setOrder(
                'created_at',
                'desc'
            )
            ->setPageSize(10)
            ->setCurPage(1);
    }

    /**
     * Get payment methode by order
     * @param Order $order
     * @return string
     */
    public function getOrderPaymentMethodName(Order $order): string
    {
        return $order->getPayment()->getMethod();
    }

    /**
     * Get payment methode by order;
     *
     * @param Order $order
     * @return string
     */
    public function getOrderShippingMethodName(Order $order): string
    {
        return $order->getShippingMethod();
    }

    /**
     * Parse order items for formatting
     *
     * @param array $orderItems
     * @return array
     */
    public function formatOrderItems(array $orderItems): array
    {
        $dataProducts = $this->formatOrderItemsForPaymentPayload($orderItems);
        $formattedItems = [];

        foreach ($dataProducts as $data) {
            if (
                isset($data['item'])
                && isset($data['product'])
                && isset($data['categories'])
            ) {
                $formattedItems[] = $this->formatItem($data);
            }
        }
        return $formattedItems;
    }

    /**
     * Format order items for payment payload
     *
     * @param array $orderItems
     * @return mixed
     */
    private function formatOrderItemsForPaymentPayload(array $orderItems)
    {
        $productsIds = $this->getProductsIds($orderItems);
        $products = $this->productHelper->getProductsItems($productsIds);
        $productsCategories = $this->productHelper->getProductsCategories($products);
        $categories = $this->formatCategoriesInArray($productsCategories);

        return $this->formatDataForPayload($orderItems, $products, $categories);
    }

    /**
     * Get all non dummy productsIds for orderItems
     *
     * @param array $orderItems
     * @return array
     */
    private function getProductsIds(array $orderItems): array
    {
        $productsIds = [];
        foreach ($orderItems as $item) {
            if (!$item->isDummy()) {
                $productsIds[] = $item->getProductId();
            }
        }

        return $productsIds;
    }

    /**
     * Format categories collection in associative array ['entity_id' => Category]
     *
     * @param mixed $productsCategories
     * @return array
     */
    private function formatCategoriesInArray($productsCategories): array
    {
        $categories = [];
        foreach ($productsCategories as $category) {
            /** @var Category $category */
            $categories[$category->getEntityId()] = $category->getName();
        }
        return $categories;
    }

    /**
     * Format orderItems, products and categories for payment payload
     *
     * @param array $orderItems
     * @param ProductCollection $products
     * @param array $categories
     * @return array
     */
    private function formatDataForPayload(array $orderItems, ProductCollection $products, array $categories): array
    {
        $dataForCartItemPayload = [];
        foreach ($orderItems as $item) {
            /** @var Item $item */
            if (!$item->isDummy()) {
                $dataForCartItemPayload[$item->getProductId()]['item'] = $item;
            }
        }

        foreach ($products as $product) {
            $dataForCartItemPayload[$product->getEntityId()]['product'] = $product;
            $productCategoriesNames = [];
            foreach ($product->getCategoryIds() as $categoryId) {
                if (isset($categories[$categoryId])) {
                    $productCategoriesNames[] = $categories[$categoryId];
                }
            }
            $dataForCartItemPayload[$product->getEntityId()]['categories'] = $productCategoriesNames;
        }

        return $dataForCartItemPayload;
    }

    /**
     * Format array with order item, product, and categories for payment payload
     *
     * @param array $data
     * @return array
     */
    private function formatItem(array $data): array
    {
        return [
            'sku' => $data['item']->getSku(),
            'vendor' => '',
            'title' => $data['item']->getName(),
            'variant_title' => $data['item']->getProductOptions()['simple_name'] ?? '',
            'quantity' => (int)$data['item']->getQtyOrdered(),
            'unit_price' => Functions::priceToCents($data['item']->getPriceInclTax()),
            'line_price' => Functions::priceToCents($data['item']->getBaseRowTotalInclTax()),
            'is_gift' => false,
            'categories' => $data['categories'],
            'url' => $data['product']->getProductUrl(),
            'picture_url' => $this->getMediaUrl($data['product']->getImage()),
            'requires_shipping' => !$data['item']->getIsVirtual(),
            'taxes_included' => (bool)$data['item']->getTaxAmount()
        ];
    }

    /**
     * Return media url for a product
     *
     * @param string | null $path
     * @return string
     */
    private function getMediaUrl(?string $path): string
    {
        if (!$path) {
            return '';
        }

        try {
            return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $path;
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Error in get media base url:', [$e->getMessage()]);
        }

        return '';
    }
}
