<?php

namespace Alma\MonthlyPayments\CustomerData;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\App\ObjectManager;

class DefaultItem extends \Magento\Checkout\CustomerData\DefaultItem
{
    protected function doGetItemData(): array
    {
        $objectManager = ObjectManager::getInstance();
        $logger= $objectManager->get(Logger::class);
        $result = parent::doGetItemData();
        $result['hasInsurance'] = $this->hasInsurance();
        $result['isProductWithInsurance'] = $this->isProductWitInsurance();
        $logger->info('Result doGetItemData', [$this->item->getData()]);
        return $result;
    }

    protected function hasInsurance(): bool
    {
        return (bool)$this->item->getAlmaInsurance();
    }

    protected function isProductWitInsurance(): bool
    {
        return $this->hasInsurance() && $this->item->getProduct()->getSku() != InsuranceHelper::ALMA_INSURANCE_SKU;
    }
	
}
