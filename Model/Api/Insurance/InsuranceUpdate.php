<?php

namespace Alma\MonthlyPayments\Model\Api\Insurance;

use Alma\API\Exceptions\AlmaException;
use Alma\MonthlyPayments\Api\Insurance\InsuranceUpdateInterface;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription;
use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription\CollectionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Rest\Request;

class InsuranceUpdate implements InsuranceUpdateInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Request
     */
    private $request;
    private $almaClient;
    private CollectionFactory $subscriptionCollection;
    private Subscription $subscription;

    /**
     * @param Request $request
     * @param Logger $logger
     */
    public function __construct(
        Request           $request,
        Logger            $logger,
        AlmaClient        $almaClient,
        CollectionFactory $subscriptionCollection,
        Subscription      $subscription
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->almaClient = $almaClient;
        $this->subscriptionCollection = $subscriptionCollection;
        $this->subscription = $subscription;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function update():void
    {
        $params = $this->request->getParams();
        $this->checkQeuryParams($params);

        $subscriptionId = $params['subscription_id'];
        $subscriptions = $this->getSubscription($subscriptionId);
        $this->checkSubscriptionResponseNotEmpty($subscriptions['subscriptions']);

        $subscription = $subscriptions['subscriptions'][0];
        $dbSubscription = $this->getDbSubscription($subscriptionId);

        $dbSubscription->setSubscriptionState($subscription['state']);
        $dbSubscription->setSubscriptionBrokerId($subscription['broker_subscription_id']);
        try {
            $this->subscription->save($dbSubscription);
        } catch (AlreadyExistsException | \Exception $e) {
            throw new Exception(__('Impossible to save subscription data'), 0, 500);
        }
    }

    /**
     * @param $subscriptions1
     * @return void
     * @throws Exception
     */
    private function checkSubscriptionResponseNotEmpty($subscriptions1): void
    {
        if (!count($subscriptions1)) {
            throw new Exception(__('Impossible to get subscription_id'), 0, 400);
        }
    }

    /**
     * @param array $params
     * @throws Exception
     */
    private function checkQeuryParams(array $params): void
    {
        if (!isset($params['subscription_id'])) {
            throw new Exception(__('Invalid subscription_id'), 0, 404);
        }
    }

    /**
     * @param $collection
     * @return void
     * @throws Exception
     */
    private function checkSubscriptionExistInDb($collection): void
    {
        if (!$collection->getFirstItem()->getId()) {
            throw new Exception(__('Subscription not found'), 0, 404);
        }
    }

    /**
     * @param string $subscriptionId
     * @return array
     * @throws Exception
     */
    private function getSubscription(string $subscriptionId): array
    {
        try {
            $subscriptions = $this->almaClient->getDefaultClient()->insurance->getSubscription(['id' => $subscriptionId]);
        } catch (AlmaException $e) {
            $this->logger->error("Error getting subscription:", [$e->getMessage()]);
            throw new Exception(__('Impossible to get subscription_id'), 0, 404, );
        }
        return $subscriptions;
    }

    /**
     * @param mixed $subscriptionId
     * @return \Alma\MonthlyPayments\Model\Insurance\Subscription
     * @throws Exception
     */
    public function getDbSubscription(mixed $subscriptionId)
    {
        $collection = $this->subscriptionCollection->create();
        $collection->addFieldToFilter('subscription_id', $subscriptionId);
        $this->checkSubscriptionExistInDb($collection);
        return $collection->getFirstItem();
    }
}
