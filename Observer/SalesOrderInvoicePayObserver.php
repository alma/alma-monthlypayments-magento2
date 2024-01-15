<?php

namespace Alma\MonthlyPayments\Observer;

use Alma\API\Exceptions\AlmaException;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
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

    public function __construct(
        Logger $logger,
        InsuranceHelper $insuranceHelper,
        AlmaClient $almaClient
    )
    {
        $this->logger = $logger;
        $this->insuranceHelper = $insuranceHelper;
        $this->almaClient = $almaClient;
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
        $this->logger->info('$subscriptionArray', [$subscriptionArray]);
        try {
            if(count($subscriptionArray) > 0) {
               $return = $this->almaClient->getDefaultClient()->insurance->subscription($subscriptionArray);
                $this->logger->info('$return', [$return]);
            }
        } catch (AlmaException $e) {
            $this->logger->error('Impossible to Subscribe', [$e->getMessage()]);
        }
    }
}
