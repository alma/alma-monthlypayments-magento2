<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\Insurance;

use Alma\API\Entities\Insurance\Subscription;
use Alma\MonthlyPayments\Helpers\InsuranceSubscriptionHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Backend\Block\Template;
use Magento\Backend\Model\Url;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class OrderViewMessage extends Template
{
    private $logger;
    private $insuranceSubscriptionHelper;
    private $url;

    public function __construct(
        Logger $logger,
        InsuranceSubscriptionHelper $insuranceSubscriptionHelper,
        Template\Context $context,
        Url $url,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
        $this->logger = $logger;
        $this->insuranceSubscriptionHelper = $insuranceSubscriptionHelper;
        $this->url = $url;
    }

    public function hasActiveInsurance(): bool
    {
        $activeStatusArray = [
            Subscription::STATE_STARTED,
            Subscription::STATE_PENDING,
            Subscription::STATE_PENDING_CANCELLATION
        ];
        $subscriptionCollection = $this->insuranceSubscriptionHelper->getCollectionSubscriptionsByOrderId((int)$this->getRequest()->getParam('order_id'));
        foreach ($subscriptionCollection as $subscription) {
            if (in_array($subscription['subscription_state'], $activeStatusArray)) {
                return true;
            }
        }
        return false;
    }

    public function getOrderDetailsLink(): string
    {
        return $this->url->getUrl('alma_monthly/insurance/subscriptiondetails', ['order_id' => $this->getRequest()->getParam('order_id')]);
    }
}
