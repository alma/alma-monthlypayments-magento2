<?php

namespace Alma\MonthlyPayments\Model;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote as BaseQuote;
use Magento\Quote\Model\Quote\Item;

class Quote extends BaseQuote
{

    /**
     * selects item in quote on which the add item is to be stacked, False no stack
     *
     * @param $product
     * @return false|Item
     */
    public function getItemByProduct($product)
    {
        $objectManager = ObjectManager::getInstance();

        $insuranceHelper= $objectManager->get(InsuranceHelper::class);
        // If insurance is in the request, do not stack
        if ($insuranceHelper->hasInsuranceInRequest()) {
            return false;
        }
        // Never stack insurance product
        try {
            $almaInsuranceProduct = $insuranceHelper->getAlmaInsuranceProduct();
            if ($product->getId() === $almaInsuranceProduct->getId()) {
                return false;
            }
        } catch (Exceptions\AlmaInsuranceProductException $e) {
            // No insurance product found, continue
        }

        /** @var \Magento\Quote\Model\Quote\Item[] $items */
        $items = $this->getItemsCollection()->getItemsByColumnValue('product_id', $product->getId());
        foreach ($items as $item) {
            if (!$item->isDeleted()
                && $item->getProduct()
                && $item->getProduct()->getStatus() !== ProductStatus::STATUS_DISABLED
                && $item->representProduct($product)
            ) {
                // Do not stack with a product with an insurance
                if ($insuranceHelper->getQuoteItemAlmaInsurance($item)) {
                    continue;
                }
                return $item;
            }
        }
        return false;
    }
}
