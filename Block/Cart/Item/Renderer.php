<?php

namespace Alma\MonthlyPayments\Block\Cart\Item;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;

class Renderer extends \Magento\Checkout\Block\Cart\Item\Renderer
{
    /**
     * Get item product name
     *
     * @return string
     */
    public function getProductName()
    {
        if ($this->hasProductName()) {
            return $this->getData('product_name');
        }
        $insuranceData = $this->_item->getData(InsuranceHelper::ALMA_INSURANCE_SKU);
        if (!$insuranceData) {
            return parent::getProductName();
        }
        $almaInsurance = json_decode($insuranceData, true);
        return $almaInsurance['name'];
    }
}
