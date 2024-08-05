<?php

namespace Alma\MonthlyPayments\Model\Quote;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;

class Item extends \Magento\Quote\Model\Quote\Item
{
    public function getProductType()
    {
        $parentType = parent::getProductType();
        $insuranceData = $this->getData(InsuranceHelper::ALMA_INSURANCE_SKU);
        if (!$insuranceData) {
            return $parentType;
        }
        $insuranceData = json_decode($insuranceData, true);
        $type = InsuranceHelper::ALMA_INSURANCE_SKU;
        if ($insuranceData['type'] === InsuranceHelper::ALMA_PRODUCT_WITH_INSURANCE_TYPE) {
            $type = $parentType . '_' . $insuranceData['type'];
        }
        return $type;
    }
}
