<?php

namespace Alma\MonthlyPayments\Observer;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;

class RemoveToCartInsuranceObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var InsuranceHelper
     */
    private $insuranceHelper;

    public function __construct(
        Logger $logger,
        InsuranceHelper $insuranceHelper
    ) {
        $this->logger = $logger;
        $this->insuranceHelper = $insuranceHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var Item $removedItem */
        $removedItem = $observer->getData('quote_item');
        $removedObjectInsuranceData = $this->insuranceHelper->getQuoteItemAlmaInsurance($removedItem);

        if (!$removedObjectInsuranceData) {
            $this->logger->info('No Insurance Data return null', []);
            return;
        }

        $removedObjectInsuranceData = json_decode($removedObjectInsuranceData, true);
        $linkToken = $removedObjectInsuranceData['link'];
        $quoteItems = $removedItem->getQuote()->getItems();

        if (InsuranceHelper::ALMA_INSURANCE_SKU === $removedItem->getSku()) {
            $productWithInsurance = $this->insuranceHelper->getProductLinkedToInsurance($linkToken, $quoteItems);
			if($productWithInsurance){
				$this->insuranceHelper->setAlmaInsuranceToQuoteItem($productWithInsurance);
			}
        } else {
            $insuranceToRemove = $this->insuranceHelper->getInsuranceProductToRemove($linkToken, $quoteItems);
			if($insuranceToRemove){
				$this->insuranceHelper->removeQuoteItemFromCart($insuranceToRemove);
			}
        }
    }
}
