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
use Alma\MonthlyPayments\Model\Data\InsuranceProduct;
use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceProductException;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session;
use Magento\ConfigurableProduct\Model\Product\Configuration\Item\ItemProductResolver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

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
     * @var ItemProductResolver
     */
    private $configurableItemProductResolver;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @param InsuranceHelper $insuranceHelper
     * @param Logger $logger
     */
    public function __construct(
        InsuranceHelper $insuranceHelper,
        Logger $logger,
        RequestInterface $request,
        ItemProductResolver $configurableItemProductResolver,
        Session $checkoutSession
    ) {
        $this->logger = $logger;
        $this->insuranceHelper = $insuranceHelper;
        $this->configurableItemProductResolver = $configurableItemProductResolver;
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(Observer $observer)
    {
        try {
            $insuranceProduct = $this->insuranceHelper->getAlmaInsuranceProduct();
        } catch (AlmaInsuranceProductException $e) {
            return;
        }

        try {
            /** @var Item $addedItemToQuote */
            $addedItemToQuote = $observer->getData('quote_item');
            $this->logger->info(' $this->checkoutSession->getQuoteId()->getEntityId();', [ $this->checkoutSession->getQuote()->getEntityId()]);
            $this->logger->info('$addedItemToQuote->getQuoteId()', [$addedItemToQuote->getQuoteId()]);

            if ($addedItemToQuote->getProduct()->getId() === $insuranceProduct->getId()) {
                $this->logger->info(' WARNING I AM ADDING INSURANCE PRODUCT', [$addedItemToQuote->getProduct()->getSku()]);
                return;
            }

            $insuranceId = $this->request->getParam('alma_insurance_id');
            if (!$insuranceId) {
                return;
            }
            $insuranceObject = $this->insuranceHelper->getInsuranceProduct($this->configurableItemProductResolver->getFinalProduct($addedItemToQuote), $insuranceId, $addedItemToQuote->getQuoteId());
            if (!$insuranceObject) {
                return;
            }


            $insuranceObject->setLinkToken($this->insuranceHelper->createLinkToken($addedItemToQuote->getProduct()->getId(), $insuranceObject->getId()));
            $this->insuranceHelper->setAlmaInsuranceToQuoteItem($addedItemToQuote, $insuranceObject->toArray(), InsuranceHelper::ALMA_PRODUCT_WITH_INSURANCE_TYPE);
            $insuranceProductInQuote = $this->addInsuranceProductToQuote($addedItemToQuote->getQuote(), $insuranceProduct, $addedItemToQuote, $insuranceObject);
            $this->insuranceHelper->setAlmaInsuranceToQuoteItem($insuranceProductInQuote, $insuranceObject->toArray(), InsuranceHelper::ALMA_INSURANCE_SKU);
        } catch (\Exception $e) {
            $this->logger->info('Error', [$e->getMessage()]);
        }
    }

    /**
     * @param Quote $quote
     * @param Product $magentoInsuranceProduct
     * @param Item $addedItemToQuote
     * @return Item
     * @throws AlmaInsuranceProductException
     */
    private function addInsuranceProductToQuote(Quote $quote, Product $magentoInsuranceProduct, Item $addedItemToQuote, InsuranceProduct $insuranceProduct): Item
    {
        try {
            $insuranceInQuote = $quote->addProduct($magentoInsuranceProduct, $this->makeAddRequest($magentoInsuranceProduct, $addedItemToQuote->getQty()));
            $insuranceInQuote->setName($insuranceProduct->getName());
            $insuranceInQuote->setCustomPrice($insuranceProduct->getFloatPrice());
            $insuranceInQuote->setOriginalCustomPrice($insuranceProduct->getFloatPrice());
            $insuranceInQuote->getProduct()->setIsSuperMode(true);
            return $insuranceInQuote;
        } catch (LocalizedException $e) {
            $message = 'Impossible to add Insurance in cart';
            $this->logger->error($message, [$e->getMessage()]);
            throw new AlmaInsuranceProductException($message, 0, $e);
        }
    }

    /**
     * @param Product $product
     * @param int $qty
     * @return DataObject
     */
    private function makeAddRequest(Product $product, int $qty = 1): DataObject
    {
        $data = [
            'product' => $product->getEntityId(),
            'qty' => $qty
        ];

        $request = new DataObject();
        $request->setData($data);

        return $request;
    }
}
