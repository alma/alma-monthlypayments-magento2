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
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Item;

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
     * OrderDataBuilder constructor.
     */
    public function __construct(Logger $logger, CategoryRepository $categoryRepository)
    {
        $this->logger = $logger;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $this->logger->info('$paymentDO', [$paymentDO]);
        $orderItems = $paymentDO->getOrder()->getItems();
        $this->logger->info('$paymentDO->getOrder()', [$paymentDO->getOrder()]);
        $this->logger->info('$orderItems', [$orderItems]);

        return [
            'cart' => [
                'items' =>  $this->formatOrderItems($orderItems)
            ],
        ];
    }

    /**
     * @param Item[] $orderItems
     * @return array
     */
    private function formatOrderItems(array $orderItems): array
    {
        $formattedItems = [];
        foreach ($orderItems as $item) {
            $formattedItems[] = $this->formatItem($item);
        }
        return $formattedItems;
    }

    /**
     * @param Item $item
     * @return array
     */
    private function formatItem(Item $item): array
    {
        $this->logger->info('Category', [$item->getProduct()->getCategoryIds()]);
        return [
            'sku' => $item->getSku(),
            'vendor' => '',
            'title' => $item->getName(),
            'variant_title' => '',
            'quantity' => (int) $item->getQtyOrdered(),
            'unit_price' => Functions::priceToCents($item->getPriceInclTax()),
            'line_price' => Functions::priceToCents($item->getBaseRowTotalInclTax()),
            'is_gift' => false,
            'categories' => $this->getCategoryNames($item->getProduct()->getCategoryIds()),
            'url' => $item->getProduct()->getProductUrl(),
            'picture_url' => $item->getProduct()->getImage(),
            'requires_shipping' => $item->getIsVirtual(),
            'taxes_included' => $item->getTaxAmount()
        ];
    }

    private function getCategoryNames(array $ids):array
    {
        $categoryNames = [];
        foreach ($ids as $id) {
            $categoryNames[] = $this->categoryRepository->get($id)->getName();
        }
        return $categoryNames;
    }
}
