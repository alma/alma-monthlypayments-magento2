<?php

namespace Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription;

use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription as ResourceModelSubscriptionAlias;
use Alma\MonthlyPayments\Model\Insurance\Subscription as ModelSubscriptionAlias;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'alma_insurance_subscription_collection';
    protected $_eventObject = 'subscription_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            ModelSubscriptionAlias::class,
            ResourceModelSubscriptionAlias::class
        );
    }

}
