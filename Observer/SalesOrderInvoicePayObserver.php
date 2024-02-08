<?php

namespace Alma\MonthlyPayments\Observer;

use Alma\API\Exceptions\AlmaException;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Item\Collection;

class SalesOrderInvoicePayObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var InsuranceHelper
     */
    private $insuranceHelper;
    /**
     * @var AlmaClient
     */
    private $almaClient;
    /**
     * @var Subscription
     */
    private $subscriptionResourceModel;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;

    public function __construct(
        Logger          $logger,
        InsuranceHelper $insuranceHelper,
        AlmaClient      $almaClient,
        Subscription    $subscriptionResourceModel,
        ApiConfigHelper $apiConfigHelper
    ) {
        $this->logger = $logger;
        $this->insuranceHelper = $insuranceHelper;
        $this->almaClient = $almaClient;
        $this->subscriptionResourceModel = $subscriptionResourceModel;
        $this->apiConfigHelper = $apiConfigHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $this->logger->info('In Sales order invoice pay observer', []);
        /** @var Invoice $invoice */
        $invoice = $observer->getEvent()->getData('invoice');
        $this->logger->info('$invoice', [$invoice]);
        $billingAddress = $invoice->getBillingAddress();
        $this->logger->info('$billingAddress', [$billingAddress]);
        $subscriber = $this->insuranceHelper->getSubscriberByAddress($billingAddress);
        $this->logger->info('$subscriber', [$subscriber]);

        /** @var Collection $invoicedItems */
        $invoicedItems = $invoice->getItems();
        $this->logger->info('$invoicedItems', [$invoicedItems]);

        $subscriptionArray = $this->insuranceHelper->getSubscriptionData($invoicedItems, $subscriber);

        // Exit if no subscription in invoice
        if (!count($subscriptionArray) > 0) {
            $this->logger->info('No subscription in invoice', [$invoice->getIncrementId()]);
            return;
        }

        $this->logger->info('$subscriptionArray', [$subscriptionArray]);
        try {
            $return = $this->almaClient->getDefaultClient()->insurance->subscription($subscriptionArray, null, null, $invoice->getOrder()->getQuoteId());
            $this->logger->info('$return', [$return]);
            if (!$return['subscriptions']) {
                $this->logger->error('Warning No subscription data in Alma return', [$return]);
                return;
            }
            $return = $return['subscriptions'];
            $dbSubscriptionToSave = $this->insuranceHelper->createDbSubscriptionArrayFromItemsAndApiResult($invoicedItems, $return, $this->apiConfigHelper->getActiveMode());
            foreach ($dbSubscriptionToSave as $dbSubscription) {
                $this->subscriptionResourceModel->save($dbSubscription);
            }
        } catch (AlreadyExistsException $e) {
            $this->logger->error('Subscription Already Exists in Database', [$e->getMessage()]);
        } catch (AlmaException $e) {
            $this->logger->error('Impossible to Subscribe', [$e->getMessage()]);
        }
    }
}