<?php

namespace Alma\MonthlyPayments\CustomerData;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Magento\Framework\App\ObjectManager;

class DefaultItem extends \Magento\Checkout\CustomerData\DefaultItem
{
    /**
     * Get item data. Template method
     *
     * @return array
     */
    protected function doGetItemData(): array
    {
        $objectManager = ObjectManager::getInstance();
        $insuranceHelper = $objectManager->get(InsuranceHelper::class);

        $result = parent::doGetItemData();

        $result['hasInsurance'] = $this->hasInsurance();
        $result['isInsuranceProduct'] = $this->isInsuranceProduct();
        $result['isProductWithInsurance'] = $this->isProductWithInsurance();
        if ($this->hasInsurance()) {
            $insuranceData = json_decode($this->item->getAlmaInsurance(), true);
            $result['insuranceName'] = $insuranceData['name'];
            $result['insuranceFiles'] = $insuranceData['files'];
        }
        if ($this->isInsuranceProduct()) {
            $result['product_name'] = $insuranceHelper->getInsuranceName($this->item);
        }
        return $result;
    }

    /**
     * Item has insurance data
     *
     * @return bool
     */
    protected function hasInsurance(): bool
    {
        return (bool)$this->item->getAlmaInsurance();
    }

    /**
     * Check if it's a product with insurance
     *
     * @return bool
     */
    protected function isProductWithInsurance(): bool
    {
        return $this->hasInsurance() && $this->item->getProduct()->getSku() != InsuranceHelper::ALMA_INSURANCE_SKU;
    }

    /**
     * Check if it's an insurance product
     *
     * @return bool
     */
    protected function isInsuranceProduct(): bool
    {
        return $this->hasInsurance() && !$this->isProductWithInsurance();
    }
}
