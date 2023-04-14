<?php
/**
 * 2018-2019 Alma SAS
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
 * @copyright 2018-2019 Alma SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace Alma\MonthlyPayments\Gateway\Request;

use Alma\MonthlyPayments\Helpers\Functions;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\StoreManagerInterface;

class CartDataBuilder implements BuilderInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Logger $logger
     * @param CategoryRepository $categoryRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Logger $logger, CategoryRepository $categoryRepository, StoreManagerInterface $storeManager)
    {
        $this->logger = $logger;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
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
     * Build cart data for payload
     *
     * @param array $buildSubject
     * @return array[]
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $orderItems = $paymentDO->getOrder()->getItems();

        return [
            'cart' => [
                'items' =>  $this->formatOrderItems($orderItems)
            ],
        ];
    }

    /**
     * Parse order items for formatting
     *
     * @param Item[] $orderItems
     * @return array
     */
    private function formatOrderItems(array $orderItems): array
    {
        $formattedItems = [];
        foreach ($orderItems as $item) {
            if (!$item->isDummy()) {
                $formattedItems[] = $this->formatItem($item);
            }
        }
        return $formattedItems;
    }

    /**
     * Format Order item for payment payload
     *
     * @param Item $item
     * @return array
     */
    private function formatItem(Item $item): array
    {
        return [
            'sku' => $item->getSku(),
            'vendor' => '',
            'title' => $item->getName(),
            'variant_title' => $item->getProductOptions()['simple_name'] ?? '',
            'quantity' => (int) $item->getQtyOrdered(),
            'unit_price' => Functions::priceToCents($item->getPriceInclTax()),
            'line_price' => Functions::priceToCents($item->getBaseRowTotalInclTax()),
            'is_gift' => false,
            'categories' => $this->getCategoryNames($item->getProduct()->getCategoryIds()),
            'url' => $item->getProduct()->getProductUrl(),
            'picture_url' => $this->getMediaUrl($item->getProduct()->getImage()),
            'requires_shipping' => !$item->getIsVirtual(),
            'taxes_included' => (bool) $item->getTaxAmount()
        ];
    }

    /**
     * Get categories name by ID
     *
     * @param array $ids
     * @return array
     */
    private function getCategoryNames(array $ids):array
    {
        $categoryNames = [];
        foreach ($ids as $id) {
            try {
                $name = $this->categoryRepository->get($id)->getName();
                $categoryNames[] = $name;
            } catch (NoSuchEntityException $e) {
                $this->logger->warning('No category for id', [$id]);
            }
        }
        return $categoryNames;
    }
}
