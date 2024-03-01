<?php

namespace Alma\MonthlyPayments\CustomerData;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Quote\Item;
use Magento\Framework\App\ObjectManager;

/**
 * Get array of last added items
 *
 * @return \Magento\Quote\Model\Quote\Item[]
 */
class Cart extends \Magento\Checkout\CustomerData\Cart
{
    protected function getRecentItems(): array
    {
        $objectManager = ObjectManager::getInstance();
        $insuranceHelper = $objectManager->get(InsuranceHelper::class);

        $items = parent::getRecentItems();
        return $insuranceHelper->reorderMiniCart($items);
    }
}
