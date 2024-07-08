<?php

namespace Alma\MonthlyPayments\Observer;

use Alma\API\Exceptions\AlmaException;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\InsuranceSendCustomerCartHelper;
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
    private $insuranceSendCustomerCartHelper;

    public function __construct(
        Logger                          $logger,
        InsuranceHelper                 $insuranceHelper,
        AlmaClient                      $almaClient,
        Subscription                    $subscriptionResourceModel,
        ApiConfigHelper                 $apiConfigHelper,
        InsuranceSendCustomerCartHelper $insuranceSendCustomerCartHelper
    ) {
        $this->logger = $logger;
        $this->insuranceHelper = $insuranceHelper;
        $this->almaClient = $almaClient;
        $this->subscriptionResourceModel = $subscriptionResourceModel;
        $this->apiConfigHelper = $apiConfigHelper;
        $this->insuranceSendCustomerCartHelper = $insuranceSendCustomerCartHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var Invoice $invoice */
        $invoice = $observer->getEvent()->getData('invoice');
        $billingAddress = $invoice->getBillingAddress();
        $subscriber = $this->insuranceHelper->getSubscriberByAddress($billingAddress);

        /** @var Collection $invoicedItems */
        $invoicedItems = $invoice->getItems();
        $this->insuranceSendCustomerCartHelper->sendCustomerCart($invoicedItems, $invoice->getOrder()->getQuoteId());
        $subscriptionArray = $this->insuranceHelper->getSubscriptionData($invoicedItems, $subscriber);

        // Exit if no subscription in invoice
        if (count($subscriptionArray) < 1) {
            $this->logger->info('No subscription in invoice', [$invoice->getIncrementId()]);
            return;
        }

        try {
            $return = $this->almaClient->getDefaultClient()->insurance->subscription(
                $subscriptionArray,
                $invoice->getOrder()->getId(),
                null,
                null,
                $invoice->getOrder()->getQuoteId()
            );
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
