<?php

namespace Alma\MonthlyPayments\Test\Unit\Observer;

use Alma\API\Client;
use Alma\API\Endpoints\Insurance;
use Alma\API\Entities\DTO\MerchantBusinessEvent\OrderConfirmedBusinessEvent;
use Alma\API\Entities\Insurance\Subscriber;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\InsuranceSendCustomerCartHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Exceptions\MerchantBusinessServiceException;
use Alma\MonthlyPayments\Model\Insurance\Subscription;
use Alma\MonthlyPayments\Observer\SalesOrderInvoicePayObserver;
use Alma\MonthlyPayments\Services\MerchantBusinessService;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Invoice;
use PHPUnit\Framework\TestCase;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Item\Collection;


class SalesOrderInvoicePayObserverTest extends TestCase
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
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;
    /**
     * @var \Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription
     */
    private $subscriptionResourceModel;
    /**
     * @var InsuranceSendCustomerCartHelper
     */
    private $insuranceSendCustomerCartHelper;

    /**
     * @var MerchantBusinessService
     */
    private $merchantBusinessService;


    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->insuranceHelper = $this->createMock(InsuranceHelper::class);
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->subscriptionResourceModel = $this->createMock(\Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription::class);
        $this->apiConfigHelper = $this->createMock(ApiConfigHelper::class);
        $this->insuranceSendCustomerCartHelper = $this->createMock(InsuranceSendCustomerCartHelper::class);
        $this->merchantBusinessService = $this->createMock(MerchantBusinessService::class);
    }

    public function testObserverMustCallGetSubscriberAndSubscriptionDataAndNullSubscriptionNotCallAlmaClient(): void
    {
        $billingAddress = $this->createMock(Address::class);
        $itemsInvoiceCollection = $this->createMock(Collection::class);
        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getBillingAddress')->willReturn($billingAddress);
        $invoice->method('getItems')->willReturn($itemsInvoiceCollection);

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getQuoteId')->willReturn(42);
        $invoice->method('getOrder')->willReturn($orderMock);

        $event = $this->createMock(Event::class);
        $event->method('getData')->willReturn($invoice);
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        $this->insuranceHelper->expects($this->once())->method('getSubscriberByAddress');
        $this->insuranceSendCustomerCartHelper->expects($this->once())->method('sendCustomerCart')->with($itemsInvoiceCollection, 42);
        $this->insuranceHelper->expects($this->once())->method('getSubscriptionData')->willReturn([]);
        $this->almaClient->expects($this->never())->method('getDefaultClient');
        $this->createSalesOrderInvoicePayObserver()->execute($observer);
    }

    public function testObserverMustCallGetSubscriberAndSubscriptionDataAndCallAlmaClientInsuranceSubscriptionAndPointAndSaveDataInDb(): void
    {
        $orderId = 35;
        $billingAddress = $this->createMock(Address::class);
        $itemsInvoiceCollection = $this->createMock(Collection::class);

        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getBillingAddress')->willReturn($billingAddress);
        $invoice->method('getItems')->willReturn($itemsInvoiceCollection);

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getQuoteId')->willReturn(42);
        $orderMock->method('getId')->willReturn($orderId);
        $invoice->method('getOrder')->willReturn($orderMock);

        $event = $this->createMock(Event::class);
        $event->method('getData')->willReturn($invoice);

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        $subscription1 = $this->createMock(\Alma\API\Entities\Insurance\Subscription::class);
        $subscription2 = $this->createMock(\Alma\API\Entities\Insurance\Subscription::class);

        $this->insuranceHelper->expects($this->once())->method('getSubscriberByAddress')->willReturn($this->subscriberFactory());
        $this->insuranceSendCustomerCartHelper->expects($this->once())->method('sendCustomerCart')->with($itemsInvoiceCollection, 42);
        $this->insuranceHelper->expects($this->once())->method('getSubscriptionData')->willReturn([$subscription1, $subscription2]);

        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint->expects($this->once())
            ->method('subscription')
            ->with([$subscription1, $subscription2], $orderId, null, null, 42)
            ->willReturn(json_decode('{"subscriptions":[{"contract_id":"insurance_contract_5LH0o7qj87xGp6sF1AGWqx","subscription_id":"subscription_298QYLM3q94luQSD34LDlr","cms_reference":"24-MB02"},{"contract_id":"insurance_contract_5LH0o7qj87xGp6sF1AGWqx","subscription_id":"subscription_2333333333333333333333","cms_reference":"24-MB02"}]}', true));

        $client = $this->createMock(Client::class);
        $client->insurance = $insuranceEndpoint;

        $this->almaClient->expects($this->once())->method('getDefaultClient')->willReturn($client);
        $dbSubscription1 = $this->createMock(Subscription::class);
        $dbSubscription2 = $this->createMock(Subscription::class);
        $this->insuranceHelper->expects($this->once())->method('createDbSubscriptionArrayFromItemsAndApiResult')->willReturn([$dbSubscription1, $dbSubscription2]);
        $this->subscriptionResourceModel->expects($this->exactly(2))->method('save');
        $this->createSalesOrderInvoicePayObserver()->execute($observer);
    }

    public function testObserverMustCallOrderConfirmedMerchantBusinessService(): void
    {
        $billingAddress = $this->createMock(Address::class);
        $itemsInvoiceCollection = $this->createMock(Collection::class);
        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getBillingAddress')->willReturn($billingAddress);
        $invoice->method('getItems')->willReturn($itemsInvoiceCollection);

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getQuoteId')->willReturn(42);
        $invoice->method('getOrder')->willReturn($orderMock);

        $event = $this->createMock(Event::class);
        $event->method('getData')->willReturn($invoice);
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        $this->insuranceHelper->expects($this->once())->method('getSubscriberByAddress');
        $this->insuranceSendCustomerCartHelper->expects($this->once())->method('sendCustomerCart')->with($itemsInvoiceCollection, 42);
        $this->insuranceHelper->expects($this->once())->method('getSubscriptionData')->willReturn([]);
        $this->almaClient->expects($this->never())->method('getDefaultClient');


        $dtoMock = $this->createMock(OrderConfirmedBusinessEvent::class);
        $this->merchantBusinessService
            ->expects($this->once())
            ->method('createOrderConfirmedBusinessEventByOrder')
            ->with($orderMock)
            ->willReturn($dtoMock);
        $this->merchantBusinessService
            ->expects($this->once())
            ->method('sendOrderConfirmedBusinessEvent')
            ->with($dtoMock);
        $this->createSalesOrderInvoicePayObserver()->execute($observer);
    }

    public function testObserverNotThrowInCasOfCreateOrderConfirmedObjectError(): void
    {
        $billingAddress = $this->createMock(Address::class);
        $itemsInvoiceCollection = $this->createMock(Collection::class);
        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getBillingAddress')->willReturn($billingAddress);
        $invoice->method('getItems')->willReturn($itemsInvoiceCollection);

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getQuoteId')->willReturn(42);
        $invoice->method('getOrder')->willReturn($orderMock);

        $event = $this->createMock(Event::class);
        $event->method('getData')->willReturn($invoice);
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        $this->insuranceHelper->expects($this->once())->method('getSubscriberByAddress');
        $this->insuranceSendCustomerCartHelper->expects($this->once())->method('sendCustomerCart')->with($itemsInvoiceCollection, 42);
        $this->insuranceHelper->expects($this->once())->method('getSubscriptionData')->willReturn([]);
        $this->almaClient->expects($this->never())->method('getDefaultClient');


        $dtoMock = $this->createMock(OrderConfirmedBusinessEvent::class);
        $this->merchantBusinessService
            ->expects($this->once())
            ->method('createOrderConfirmedBusinessEventByOrder')
            ->with($orderMock)
            ->willThrowException(new MerchantBusinessServiceException('Error in DTO creation'));
        $this->merchantBusinessService
            ->expects($this->never())
            ->method('sendOrderConfirmedBusinessEvent')
            ->with($dtoMock);
        $this->createSalesOrderInvoicePayObserver()->execute($observer);
    }

    private function createSalesOrderInvoicePayObserver(): SalesOrderInvoicePayObserver
    {
        return new SalesOrderInvoicePayObserver(...$this->getDependency());
    }

    private function getDependency(): array
    {
        return [
            $this->logger,
            $this->insuranceHelper,
            $this->almaClient,
            $this->subscriptionResourceModel,
            $this->apiConfigHelper,
            $this->insuranceSendCustomerCartHelper,
            $this->merchantBusinessService
        ];
    }

    private function subscriberFactory(): Subscriber
    {
        return new Subscriber(
            'test@almapay.com',
            '0601020304',
            'John',
            'Doe',
            'Rue des petites ecuries',
            'ligne 2',
            '75010',
            'Paris',
            'FR',
            null
        );
    }
}
