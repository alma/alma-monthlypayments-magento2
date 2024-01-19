<?php

namespace Alma\MonthlyPayments\Test\Unit\Observer;

use Alma\API\Client;
use Alma\API\Endpoints\Insurance;
use Alma\API\Entities\Insurance\Subscriber;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Observer\SalesOrderInvoicePayObserver;
use Magento\Customer\Model\Session;
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
     * @var Session
     */
    private $session;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->insuranceHelper = $this->createMock(InsuranceHelper::class);
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->session = $this->createMock(Session::class);

    }

    public function testObserverMustCallGetSubscriberAndSubscriptionDataAndNullSubscriptionNotCallAlmaClient(): void
    {
        $billingAddress = $this->createMock(Address::class);
        $itemsInvoiceCollection = $this->createMock(Collection::class);
        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getBillingAddress')->willReturn($billingAddress);
        $invoice->method('getItems')->willReturn($itemsInvoiceCollection);
        $event = $this->createMock(Event::class);
        $event->method('getData')->willReturn($invoice);
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);
        $this->insuranceHelper->expects($this->once())->method('getSubscriberByAddress');
        $this->insuranceHelper->expects($this->once())->method('getSubscriptionData')->willReturn([]);
        $this->almaClient->expects($this->never())->method('getDefaultClient');
        $this->createSalesOrderInvoicePayObserver()->execute($observer);
    }

    public function testObserverMustCallGetSubscriberAndSubscriptionDataAndCallAlmaClient(): void
    {
        $billingAddress = $this->createMock(Address::class);
        $itemsInvoiceCollection = $this->createMock(Collection::class);

        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getBillingAddress')->willReturn($billingAddress);
        $invoice->method('getItems')->willReturn($itemsInvoiceCollection);

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getQuoteId')->willReturn('42');
        $invoice->method('getOrder')->willReturn($orderMock);

        $event = $this->createMock(Event::class);
        $event->method('getData')->willReturn($invoice);

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        $this->insuranceHelper->expects($this->once())->method('getSubscriberByAddress');
        $this->insuranceHelper->expects($this->once())->method('getSubscriptionData')->willReturn([$this->subscriberFactory()]);

        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint->expects($this->once())->method('subscription')->with([$this->subscriberFactory()], null, null, '42');

        $client = $this->createMock(Client::class);
        $client->insurance = $insuranceEndpoint;

        $this->almaClient->expects($this->once())->method('getDefaultClient')->willReturn($client);
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
            $this->session
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
