<?php

namespace Alma\MonthlyPayments\CustomerData;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote\Item;

/**
 * Get array of last added items
 *
 * @return Item[]
 */
class Cart extends \Magento\Checkout\CustomerData\Cart
{
    /**
     * Get cart recent items
     *
     * @return array|Item[]
     */
    protected function getRecentItems(): array
    {
        $objectManager = ObjectManager::getInstance();
        $insuranceHelper = $objectManager->get(InsuranceHelper::class);

        $items = parent::getRecentItems();
        return $insuranceHelper->reorderMiniCart($items);
    }
}
