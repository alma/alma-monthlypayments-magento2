<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription\CollectionFactory;
use Alma\MonthlyPayments\Model\Insurance\Subscription;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Validator\Exception;

class InsuranceSubscriptionHelper extends AbstractHelper
{
    private $subscriptionCollection;
    public function __construct(
        CollectionFactory $subscriptionCollection,
        Context $context
    ) {
        parent::__construct($context);
        $this->subscriptionCollection = $subscriptionCollection;
    }

    /**
     * @param mixed $subscriptionId
     * @return mixed
     * @throws Exception
     */
    public function getDbSubscription(mixed $subscriptionId): Subscription
    {
        $collection = $this->subscriptionCollection->create();
        $collection->addFieldToFilter('subscription_id', $subscriptionId);
        $this->checkSubscriptionExistInDb($collection);
        return $collection->getFirstItem();
    }
    private function checkSubscriptionExistInDb($collection): void
    {
        if (!$collection->getFirstItem()->getId()) {
            throw new Exception(__('Subscription not found'));
        }
    }
}
