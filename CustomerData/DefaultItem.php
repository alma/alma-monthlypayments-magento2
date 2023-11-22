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
        $result['isProductWithInsurance'] = $this->isProductWithInsurance();

        if ($this->isInsuranceProduct()) {
            $almaInsurance = json_decode($this->item->getAlmaInsurance(), true);
            $result['product_name'] = $result['product_name'] . ' - ' . $almaInsurance['name'] . ' - ' . $almaInsurance['parent_name'];
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
