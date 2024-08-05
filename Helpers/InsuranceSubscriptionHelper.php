<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceSubscriptionException;
use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription\CollectionFactory;
use Alma\MonthlyPayments\Model\Insurance\Subscription;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

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
     * @param string $subscriptionId
     * @return mixed
     * @throws AlmaInsuranceSubscriptionException
     */
    public function getDbSubscription(string $subscriptionId): Subscription
    {
        $collection = $this->subscriptionCollection->create();
        $collection->addFieldToFilter('subscription_id', $subscriptionId);
        $this->checkSubscriptionExistInDb($collection);
        return $collection->getFirstItem();
    }

    /**
     * @param $collection
     * @return void
     * @throws AlmaInsuranceSubscriptionException
     */
    private function checkSubscriptionExistInDb($collection): void
    {
        if (!$collection->getFirstItem()->getId()) {
            throw new AlmaInsuranceSubscriptionException(__('Subscription not found'));
        }
    }

    public function getCollectionSubscriptionsByOrderId(int $orderId): array
    {
        $collection = $this->subscriptionCollection->create();
        $collection->addFieldToFilter('order_id', $orderId);
        return $collection->getData();
    }

    public function getNameStatus(string $key): string
    {
        $status = [
            'started' => __('Active'),
            'pending_cancellation' => __('Pending Cancellation'),
            'canceled' => __('Canceled'),
            'failed' => __('Failed'),
            'pending' => __('Pending'),
        ];
        return $status[$key] ?? $key;
    }
}
