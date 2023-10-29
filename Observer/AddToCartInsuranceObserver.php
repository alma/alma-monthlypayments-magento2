<?php
/**
 * 2018-2021 Alma SAS
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
 * @copyright 2018-2021 Alma SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace Alma\MonthlyPayments\Observer;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceProductException;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\ItemFactory;

class AddToCartInsuranceObserver implements ObserverInterface
{
    /**
     * @var InsuranceHelper
     */
    private $insuranceHelper;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Item\Processor
     */
    private $itemProcessor;
    /**
     * @var DataObjectFactory
     */
    private $objectFactory;
    /**
     * @var ItemFactory
     */
    private $quoteItemFactory;

    /**
     * @param InsuranceHelper $insuranceHelper
     * @param Logger $logger
     */
    public function __construct(
        InsuranceHelper $insuranceHelper,
        Item\Processor $itemProcessor,
        DataObjectFactory $objectFactory,
        ItemFactory $quoteItemFactory,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->insuranceHelper = $insuranceHelper;
        $this->itemProcessor = $itemProcessor;
        $this->objectFactory = $objectFactory;
        $this->quoteItemFactory = $quoteItemFactory;
    }

    public function execute(Observer $observer)
    {
        try {
            $insuranceProduct = $this->insuranceHelper->getAlmaInsuranceProduct();
        } catch (AlmaInsuranceProductException $e) {
            return;
        }

        try {
            /** @var Item $quoteItem */
            $quoteItem = $observer->getData('quote_item');

            if ($quoteItem->getProduct()->getId() === $insuranceProduct->getId()) {
                $this->logger->info(' WARNING I AM ADDING INSURANCE PRODUCT', [$quoteItem->getProduct()]);
                return;
            }

            $insuranceObject = $this->insuranceHelper->getInsuranceParamsInRequest();
            if ($insuranceObject) {
                $insuranceObject->setLinkToken($this->insuranceHelper->createLinkToken($quoteItem->getProduct()->getId(), $insuranceObject->getId()));
                $this->insuranceHelper->setAlmaInsuranceToQuoteItem($quoteItem, $insuranceObject->toArray());
            }
            $insuranceProductInQuote = $this->addInsuranceProductToQuote($quoteItem->getQuote(), $insuranceProduct);
            $this->insuranceHelper->setAlmaInsuranceToQuoteItem($insuranceProductInQuote, $insuranceObject->toArray());
        } catch (\Exception $e) {
            $this->logger->info('Error', [$e->getMessage()]);
        }
    }

    /**
     * @param Quote $quote
     * @param Product $insuranceProduct
     * @return Item
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addInsuranceProductToQuote(Quote $quote, Product $insuranceProduct): Item
    {
        try {
            $insuranceInQuote = $quote->addProduct($insuranceProduct);
            $price = rand(100, 200);
            $insuranceInQuote->setCustomPrice($price);
            $insuranceInQuote->setOriginalCustomPrice($price);
            $insuranceInQuote->getProduct()->setIsSuperMode(true);
            return $insuranceInQuote;
        } catch (LocalizedException $e) {
            $message = 'Impossible to add Insurance in cart';
            $this->logger->error($message, [$e->getMessage()]);
            throw new AlmaInsuranceProductException($message, 0, $e);
        }
    }
}
