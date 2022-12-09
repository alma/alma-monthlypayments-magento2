<?php
/**
 * 2018 Alma / Nabla SAS
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
 * @author    Alma / Nabla SAS <contact@getalma.eu>
 * @copyright 2018 Alma / Nabla SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 *
 */

namespace Alma\MonthlyPayments\Model\Data;

use Alma\MonthlyPayments\Helpers\Functions;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\ProductImage;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\Resolver;
use Magento\Payment\Gateway\Data\Quote\AddressAdapter;
use Magento\Quote\Model\Quote as MagentoQuote;
use Magento\Quote\Model\Quote\Item;

class Quote
{

    /**
     * @var ProductImage
     */
    private $productImageHelper;
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;
    /**
     * @var Resolver
     */
    private $locale;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * Quote constructor.
     *
     * @param ProductImage $productImageHelper
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Logger $logger
     * @param CollectionFactory $collectionFactory
     * @param Resolver $locale
     */
    public function __construct(
        ProductImage $productImageHelper,
        CategoryRepositoryInterface $categoryRepository,
        Logger $logger,
        CollectionFactory $collectionFactory,
        Resolver $locale
    ) {
        $this->productImageHelper = $productImageHelper;
        $this->categoryRepository = $categoryRepository;
        $this->locale             = $locale;
        $this->logger             = $logger;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Create payload to eligibility request
     *
     * @param MagentoQuote $quote
     * @param array        $installmentsQuery
     *
     * @return array
     */
    public function eligibilityDataFromQuote(MagentoQuote $quote, array $installmentsQuery): array
    {
        $shippingAddress = new AddressAdapter($quote->getShippingAddress());
        $billingAddress  = new AddressAdapter($quote->getBillingAddress());
        $billingCountry  = $billingAddress->getCountryId();
        $shippingCountry = $shippingAddress->getCountryId();

        $data = [
            'online'          => 'online',
            'purchase_amount' => Functions::priceToCents((float) $quote->getGrandTotal()),
            'locale'          => $this->locale->getLocale(),
            'queries'         => $installmentsQuery,
        ];
        if ($billingCountry) {
            $data['billing_address'] = ['country' => $billingCountry];
        }
        if ($shippingCountry) {
            $data['shipping_address'] = ['country' => $shippingCountry];
        }
        return $data;
    }

    /**
     * Create Line_item payload for payment
     *
     * @param MagentoQuote $quote
     * @return array
     */
    public function lineItemsDataFromQuote(MagentoQuote $quote): array
    {
        $data = [];
        $items = $quote->getAllVisibleItems();

        /** @var Item $item */
        foreach ($items as $item) {
            $product = $item->getProduct();

            $data[] = [
                'title' => $item->getName(),
                'category' => $this->getProductCategories($product),
                'unit_price' => Functions::priceToCents($item->getPrice()),
                'quantity' => $item->getQty(),
                'url' => $product->getUrlInStore(),
                'picture_url' => $this->productImageHelper->getImageUrl(
                    $product,
                    'product_page_image_small',
                    ['width' => 512, 'height' => 512]
                ),
                'is_virtual' => $item->getIsVirtual(),
            ];
        }
        $this->logger->info('$data', [$data]);
        return $data;
    }

    /**
     * Get product categories for line_item payload
     *
     * @param Product $product
     * @return array
     */
    private function getProductCategories(Product $product): array
    {
        $categoryIds = $product->getCategoryIds();
        if (empty($categoryIds)) {
            return [];
        }
        try {
            $categoryCollection = $this->getCategoryCollection($categoryIds);
        } catch (LocalizedException $e) {
            $this->logger->info(
                'Impossible to create category collection for product',
                [
                    'product ID '=> $product->getId(),
                    'Exception Message' => $e->getMessage()
                ]
            );
            return [];
        }
        $productCategories = [];
        foreach ($categoryCollection as $cate) {
            /** @var $cate Category */
            $productCategories[] = $cate->getData('name');
        }
        return $productCategories;
    }

    /**
     * Get category collection by
     *
     * @param array $categoryIds
     * @param int $level
     *
     * @return Collection
     * @throws LocalizedException
     */
    public function getCategoryCollection(array $categoryIds, int $level = 2): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('entity_id', $categoryIds);
        $collection->addIsActiveFilter();
        // select categories of certain level
        $collection->addLevelFilter($level);

        return $collection;
    }
}
