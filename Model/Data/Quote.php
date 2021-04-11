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

use Alma\MonthlyPayments\Helpers\ProductImage;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote as MagentoQuote;
use Magento\Payment\Gateway\Data\Quote\AddressAdapter;
use Alma\MonthlyPayments\Helpers\Functions;
use Magento\Quote\Model\Quote\Item;

class Quote {

    /**
     * @var Customer
     */
    private $customerData;
    /**
     * @var ProductImage
     */
    private $productImageHelper;
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    public function __construct(
        Customer $customerData,
        ProductImage $productImageHelper,
        CategoryRepositoryInterface $categoryRepository
    )
    {
        $this->customerData = $customerData;
        $this->productImageHelper = $productImageHelper;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param MagentoQuote $quote
     * @param int|array $installmentsCounts
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     */
    public function paymentDataFromQuote(MagentoQuote $quote, $installmentsCounts = 3): array
    {
        $shippingAddress = new AddressAdapter($quote->getShippingAddress());
        $billingAddress = new AddressAdapter($quote->getBillingAddress());

        $customer = $quote->getCustomer();

        $data = [
            'payment' => [
                'purchase_amount' => Functions::priceToCents((float)$quote->getGrandTotal()),
                'installments_count' => $installmentsCounts,
                'shipping_address' => Address::dataFromAddress($shippingAddress),
                'billing_address' => Address::dataFromAddress($billingAddress),
            ],
            'customer' => $this->customerData->dataFromCustomer($customer, [$billingAddress, $shippingAddress])
        ];

        return $data;
    }

    /**
     * @param MagentoQuote $quote
     * @return array
     */
    public function lineItemsDataFromQuote(MagentoQuote $quote)
    {
        $data = [];
        $items = $quote->getAllItems();

        /** @var Item $item */
        foreach ($items as $item) {
            $product = $item->getProduct();

            $data[] = [
                'title' => $item->getName(),
                'category' => $this->getProductCategories($product),
                'unit_price' => Functions::priceToCents($item->getPrice()),
                'quantity' => $item->getQty(),
                'url' => $product->getUrlInStore(),
                'picture_url' => $this->productImageHelper->getImageUrl($product, 'product_page_image_small', ['width' => 512, 'height' => 512]),
                'is_virtual' => $item->getIsVirtual(),
            ];
        }

        return $data;
    }

    private function getProductCategories(Product $product)
    {
        $paths = [];
        $categoryIds = $product->getAvailableInCategories();

        // Find each category the product belongs to
        foreach ($categoryIds as $categoryId) {
            try {
                $category = $this->categoryRepository->get($categoryId);
            } catch (NoSuchEntityException $e) {
                continue;
            }

            $components = explode('/', $category->getPath());
            if (count($components) <= 1) {
                continue;
            }
            $components = array_slice($components, 1);

            // Get the full category path for that category
            $path = "";
            foreach ($components as $component) {
                $path .= "/";

                try {
                    $cat = $this->categoryRepository->get($component);
                } catch (NoSuchEntityException $e) {
                    continue;
                }

                $path .= $cat->getName();
            }

            $paths[] = $path;
        }

        // Only keep leaves
        $categories = [];
        foreach ($paths as $path) {
            $merge = false;
            foreach ($paths as $otherPath) {
                if ($path != $otherPath && mb_strpos($otherPath, $path) === 0) {
                    $merge = true;
                    break;
                }
            }

            if ($merge) {
                continue;
            }

            $categories[] = $path;
        }

        return $categories;
    }
}
