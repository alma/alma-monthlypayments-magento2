<?php

namespace Alma\MonthlyPayments\Model;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote as BaseQuote;

class Quote extends BaseQuote
{
    public function getItemByProduct($product)
    {
        $objectManager = ObjectManager::getInstance();

        $insuranceHelper= $objectManager->get(InsuranceHelper::class);

        try {
            $almaInsuranceProduct = $insuranceHelper->getAlmaInsuranceProduct();
        } catch (Exceptions\AlmaInsuranceProductException $e) {
        }

        if ($insuranceHelper->hasInsuranceInRequest()) {
            return false;
        }

        if (isset($almaInsuranceProduct) && $product->getId() === $almaInsuranceProduct->getId()) {
            return false;
        }

        /** @var \Magento\Quote\Model\Quote\Item[] $items */
        $items = $this->getItemsCollection()->getItemsByColumnValue('product_id', $product->getId());
        foreach ($items as $item) {
            if (!$item->isDeleted()
                && $item->getProduct()
                && $item->getProduct()->getStatus() !== ProductStatus::STATUS_DISABLED
                && $item->representProduct($product)
            ) {
                if ($insuranceHelper->getQuoteItemAlmaInsurance($item)) {
                    continue;
                }
                return $item;
            }
        }
        return false;
    }
}
