<?php

namespace Alma\MonthlyPayments\Test\Unit\Observer;

use Alma\API\Entities\DTO\MerchantBusinessEvent\OrderConfirmedBusinessEvent;

use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Exceptions\MerchantBusinessServiceException;
use Alma\MonthlyPayments\Observer\SalesOrderInvoicePayObserver;
use Alma\MonthlyPayments\Services\MerchantBusinessService;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
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
     * @var MerchantBusinessService
     */
    private $merchantBusinessService;


    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->merchantBusinessService = $this->createMock(MerchantBusinessService::class);
    }


    public function testObserverMustCallOrderConfirmedMerchantBusinessService(): void
    {
        $itemsInvoiceCollection = $this->createMock(Collection::class);
        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getItems')->willReturn($itemsInvoiceCollection);

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getQuoteId')->willReturn(42);
        $invoice->method('getOrder')->willReturn($orderMock);

        $event = $this->createMock(Event::class);
        $event->method('getData')->willReturn($invoice);
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);


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
        $itemsInvoiceCollection = $this->createMock(Collection::class);
        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getItems')->willReturn($itemsInvoiceCollection);

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getQuoteId')->willReturn(42);
        $invoice->method('getOrder')->willReturn($orderMock);

        $event = $this->createMock(Event::class);
        $event->method('getData')->willReturn($invoice);
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);


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
            $this->merchantBusinessService
        ];
    }
}
