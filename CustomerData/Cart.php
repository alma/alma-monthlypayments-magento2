<?php

namespace Alma\MonthlyPayments\CustomerData;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\App\ObjectManager;

/**
 * Get array of last added items
 *
 * @return \Magento\Quote\Model\Quote\Item[]
 */
class Cart extends \Magento\Checkout\CustomerData\Cart
{
	protected function getRecentItems()
	{
		$objectManager = ObjectManager::getInstance();
		$logger= $objectManager->get(Logger::class);
		$insuranceHelper= $objectManager->get(InsuranceHelper::class);

		$items =  parent::getRecentItems();
		$items = $insuranceHelper->reorderMiniCart($items);
		return $items;
	}
}
