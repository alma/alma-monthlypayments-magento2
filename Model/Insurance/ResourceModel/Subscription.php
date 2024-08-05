<?php

namespace Alma\MonthlyPayments\Model\Insurance\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Subscription extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('alma_insurance_subscription', 'entity_id');
    }
}
