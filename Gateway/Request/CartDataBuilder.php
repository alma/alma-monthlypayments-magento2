<?php
/**
 * 2018-2023 Alma SAS
 *
 * THE MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
 * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @author    Alma SAS <contact@getalma.eu>
 * @copyright 2018-2023 Alma SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace Alma\MonthlyPayments\Gateway\Request;

use Alma\MonthlyPayments\Helpers\Functions;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\ProductHelper;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\StoreManagerInterface;

/**
 * CartDataBuilder build Cart Data for Alma credit Payment Payload
 */
class CartDataBuilder implements BuilderInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param ProductHelper $productHelper
     */
    public function __construct(
        Logger $logger,
        StoreManagerInterface $storeManager,
        ProductHelper $productHelper
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->productHelper = $productHelper;
    }

    /**
     * Build cart data for payload
     *
     * @param array $buildSubject
     * @return array[]
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $orderItems = $paymentDO->getOrder()->getItems();
        $productsIds = $this->getProductsIds($orderItems);
        $products = $this->productHelper->getProductsItems($productsIds);
        $productsCategories = $this->productHelper->getProductsCategories($products);
        $categories = $this->formatCategoriesInArray($productsCategories);
        $dataForCartItemPayload = $this->formatDataForPayload($orderItems, $products, $categories);

        return [
            'cart' => [
                'items' =>  $this->formatOrderItems($dataForCartItemPayload)
            ],
        ];
    }

    /**
     * Parse order items for formatting
     *
     * @param array $dataProducts
     * @return array
     */
    private function formatOrderItems(array $dataProducts): array
    {
        $formattedItems = [];

        foreach ($dataProducts as $data) {
            if (isset($data['item']) && isset($data['product']) && isset($data['categories'])) {
                $formattedItems[] = $this->formatItem($data);
            } else {
                $this->logger->warning(' Error with cart data payload', [$data]);
            }
        }
        return $formattedItems;
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
            'quantity' => (int) $data['item']->getQtyOrdered(),
            'unit_price' => Functions::priceToCents($data['item']->getPriceInclTax()),
            'line_price' => Functions::priceToCents($data['item']->getBaseRowTotalInclTax()),
            'is_gift' => false,
            'categories' => $data['categories'],
            'url' => $data['product']->getProductUrl(),
            'picture_url' => $this->getMediaUrl($data['product']->getImage()),
            'requires_shipping' => !$data['item']->getIsVirtual(),
            'taxes_included' => (bool) $data['item']->getTaxAmount()
        ];
    }

    /**
     * Return media url for a product
     *
     * @param string $path
     * @return string
     */
    private function getMediaUrl(string $path): string
    {
        $url = '';

        try {
            $url = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $path;
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Error in get media base url:', [$e->getMessage()]);
        }

        return $url;
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
     * @param Collection $productsCategories
     * @return array
     */
    private function formatCategoriesInArray(Collection $productsCategories): array
    {

        $categories = [];
        foreach ($productsCategories as $category) {
            /** @var Category $category */
            $categories[$category->getEntityId()] = $category->getName();
        }
        return $categories;
    }

    /**
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
            /** @var Product $product */
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
}
