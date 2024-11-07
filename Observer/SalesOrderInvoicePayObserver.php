<?php

namespace Alma\MonthlyPayments\Observer;

use Alma\API\Exceptions\AlmaException;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\InsuranceSendCustomerCartHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription;
use Alma\MonthlyPayments\Services\LocalApiService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;
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

    private $localApiService;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    public function __construct(
        Logger                          $logger,
        InsuranceHelper                 $insuranceHelper,
        AlmaClient                      $almaClient,
        Subscription                    $subscriptionResourceModel,
        ApiConfigHelper                 $apiConfigHelper,
        InsuranceSendCustomerCartHelper $insuranceSendCustomerCartHelper,
        LocalApiService                 $localApiService,
        QuoteRepository                 $quoteRepository
    )
    {
        $this->logger = $logger;
        $this->insuranceHelper = $insuranceHelper;
        $this->almaClient = $almaClient;
        $this->subscriptionResourceModel = $subscriptionResourceModel;
        $this->apiConfigHelper = $apiConfigHelper;
        $this->insuranceSendCustomerCartHelper = $insuranceSendCustomerCartHelper;
        $this->localApiService = $localApiService;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var Invoice $invoice */
        $invoice = $observer->getEvent()->getData('invoice');
        $billingAddress = $invoice->getBillingAddress();

        $this->sendMerchantEventOrderConfirmed($invoice);

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


    private function sendMerchantEventOrderConfirmed(Invoice $invoice)
    {
        $paymentId = $invoice->getorder()->getPayment()->getAdditionalInformation()['PAYMENT_ID'] ?? null;
        $planKey = $invoice->getorder()->getPayment()->getAdditionalInformation()['selectedPlan'] ?? '';
        $isPayNow = PaymentPlansHelper::PAY_NOW_KEY === $planKey;
        $isBNPL = !$isPayNow && preg_match('!general:[\d]+:[\d]+:[\d]+!', $planKey);
        try {
            $quote = $this->quoteRepository->get($invoice->getOrder()->getQuoteId());
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Error getting quote', [$e->getMessage()]);
            return;
        }
        $eventDetails = [
            'is_alma_p1x' => $isPayNow,
            'is_alma_bnpl' => $isBNPL,
            'was_bnpl_eligible' => (bool)$quote->getData('alma_bnpl_eligibility'),
            'order_id' => $invoice->getOrder()->getId(),
            'cart_id' => $invoice->getOrder()->getQuoteId(),
            'alma_payment_id' => $paymentId,
        ];
        try {
            $this->logger->info('Payload Send to the API', ['event_type' => 'order_confirmed', 'event_details' => $eventDetails]);
            $this->localApiService->sendPostRequest('order_confirmed', $eventDetails);
        } catch (\Exception $e) {
            $this->logger->error('Error sending order_confirmed event', [$e->getMessage()]);
        }
    }
}
