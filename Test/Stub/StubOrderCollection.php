<?php

namespace Alma\MonthlyPayments\Test\Stub;

use ArrayObject;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;

/**
 * Class StubOrderCollection
 *
 * @package Alma\MonthlyPayments\Test\Unit\Helpers\ShareOfCheckout
 */
class StubOrderCollection extends ArrayObject implements OrderSearchResultInterface
{

    public function getItems()
    {
        // TODO: Implement getItems() method.
    }

    public function setItems(array $items = null)
    {
        // TODO: Implement setItems() method.
    }

    public function getSearchCriteria()
    {
        // TODO: Implement getSearchCriteria() method.
    }

    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria)
    {
        // TODO: Implement setSearchCriteria() method.
    }

    public function getTotalCount()
    {
        // TODO: Implement getTotalCount() method.
    }

    public function setTotalCount($totalCount)
    {
        // TODO: Implement setTotalCount() method.
    }
}
