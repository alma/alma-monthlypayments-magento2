<?php

namespace Alma\MonthlyPayments\Model\Api\Insurance;

use Alma\API\Exceptions\AlmaException;
use Alma\MonthlyPayments\Api\Insurance\InsuranceUpdateInterface;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Functions;
use Alma\MonthlyPayments\Helpers\InsuranceSubscriptionHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceSubscriptionException;
use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription;
use Magento\Backend\Model\Url;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Notification\NotifierPool;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Model\OrderRepository;

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
    private $insuranceSubscriptionHelper;
    private $subscription;
    private $notifierPool;
    private $orderRepository;
    private $url;

    /**
     * @param Request $request
     * @param Logger $logger
     * @param AlmaClient $almaClient
     * @param InsuranceSubscriptionHelper $insuranceSubscriptionHelper
     * @param Subscription $subscription
     * @param NotifierPool $notifierPool
     * @param OrderRepository $orderRepository
     * @param Url $url
     */
    public function __construct(
        Request                     $request,
        Logger                      $logger,
        AlmaClient                  $almaClient,
        InsuranceSubscriptionHelper $insuranceSubscriptionHelper,
        Subscription                $subscription,
        NotifierPool                $notifierPool,
        OrderRepository             $orderRepository,
        Url                         $url
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->almaClient = $almaClient;
        $this->subscription = $subscription;
        $this->notifierPool = $notifierPool;
        $this->orderRepository = $orderRepository;
        $this->url = $url;
        $this->insuranceSubscriptionHelper = $insuranceSubscriptionHelper;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function update(): void
    {
        $params = $this->request->getParams();
        $this->checkQueryParams($params);

        $subscriptionId = $params['subscription_id'];
        $subscriptions = $this->getSubscriptions($subscriptionId);
        $this->checkSubscriptionResponseNotEmpty($subscriptions['subscriptions']);

        $subscription = $subscriptions['subscriptions'][0];
        try {
            $dbSubscription = $this->insuranceSubscriptionHelper->getDbSubscription($subscriptionId);
        } catch (AlmaInsuranceSubscriptionException $e) {
            throw new Exception(__('Invalid subscription_id'), 0, 404);
        }

        if (\Alma\API\Entities\Insurance\Subscription::STATE_CANCELLED === $subscription['state']) {
            if (!$dbSubscription->getCancellationDate()) {
                $dbSubscription->setCancellationDate(new \DateTime());
            }
            $order = $this->orderRepository->get($dbSubscription->getOrderId());
            $this->notifierPool->addMajor(
                sprintf(__('Alma Insurance: Order %s - Cancelled insurance subscriptions need to be refunded'), $order->getIncrementId()),
                sprintf(__('<p>The Insurance %s at %s€ for the product %s has been cancelled. Please refund the customer.</p><p><b>**Action Required: Refund the customer for the affected subscriptions.**</b></p></p><p>Thank you.</p>'), $dbSubscription['name'], (string)Functions::priceFromCents($dbSubscription['subscription_amount']), $dbSubscription['linked_product_name']),
                $this->url->getUrl('sales/order/view', ['order_id' => $order->getId()])
            );
        }

        $dbSubscription->setSubscriptionState($subscription['state']);
        $dbSubscription->setSubscriptionBrokerId($subscription['broker_subscription_id']);
        $dbSubscription->setSubscriptionBrokerReference($subscription['broker_subscription_reference']);
        try {
            $this->subscription->save($dbSubscription);
        } catch (AlreadyExistsException|\Exception $e) {
            throw new Exception(__('Impossible to save subscription data'), 0, 500);
        }
    }

    /**
     * @param $subscriptions
     * @return void
     * @throws Exception
     */
    private function checkSubscriptionResponseNotEmpty($subscriptions): void
    {
        if (!count($subscriptions)) {
            throw new Exception(__('Impossible to get subscription_id'), 0, 400);
        }
    }

    /**
     * @param array $params
     * @throws Exception
     */
    private function checkQueryParams(array $params): void
    {
        if (!isset($params['subscription_id'])) {
            throw new Exception(__('Invalid subscription_id'), 0, 404);
        }
    }

    /**
     * @param string $subscriptionId
     * @return array
     * @throws Exception
     */
    private function getSubscriptions(string $subscriptionId): array
    {
        try {
            $subscriptions = $this->almaClient->getDefaultClient()->insurance->getSubscription(['id' => $subscriptionId]);
        } catch (AlmaException $e) {
            $this->logger->error("Error getting subscription:", [$e->getMessage()]);
            throw new Exception(__('Impossible to get subscription_id'), 0, 404);
        }
        return $subscriptions;
    }
}
