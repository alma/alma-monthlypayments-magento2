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
use Alma\MonthlyPayments\Helpers\ProductImage;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * Quote constructor.
     *
     * @param ProductImage $productImageHelper
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Resolver $locale
     */
    public function __construct(
        ProductImage $productImageHelper,
        CategoryRepositoryInterface $categoryRepository,
        Resolver $locale
    ) {
        $this->productImageHelper = $productImageHelper;
        $this->categoryRepository = $categoryRepository;
        $this->locale             = $locale;
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
                'picture_url' => $this->productImageHelper->getImageUrl(
                    $product,
                    'product_page_image_small',
                    ['width' => 512, 'height' => 512]
                ),
                'is_virtual' => $item->getIsVirtual(),
            ];
        }

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
        $paths = [];
        $categoryIds = $product->getAvailableInCategories();

        // Find each category the product belongs to
        foreach ($categoryIds as $categoryId) {
            try {
                $category = $this->categoryRepository->get($categoryId);
            } catch (NoSuchEntityException $e) {
                continue;
            }

            $components = explode('/', (string)$category->getPath());
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
