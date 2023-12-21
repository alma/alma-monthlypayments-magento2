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
        $insuranceHelper= $objectManager->get(InsuranceHelper::class);

        $result = parent::doGetItemData();

		$result['hasInsurance'] = $this->hasInsurance();
		$result['isInsuranceProduct'] = $this->isInsuranceProduct();
		$result['isProductWithInsurance'] = $this->isProductWithInsurance();

        if ($this->isInsuranceProduct()) {
            $result['product_name'] = $insuranceHelper->getInsuranceName($this->item);
        }
        return $result;
    }

    protected function hasInsurance(): bool
    {
        return (bool)$this->item->getAlmaInsurance();
    }

    protected function isProductWithInsurance(): bool
    {
        return $this->hasInsurance() && $this->item->getProduct()->getSku() != InsuranceHelper::ALMA_INSURANCE_SKU;
    }

    protected function isInsuranceProduct(): bool
    {
        return $this->hasInsurance() && !$this->isProductWithInsurance();
    }
}
