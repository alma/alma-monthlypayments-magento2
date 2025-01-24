<?php

namespace Alma\MonthlyPayments\Test\Unit\Observer;

use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Observer\SalesQuoteSaveAfterObserver;
use Alma\MonthlyPayments\Services\MerchantBusinessService;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;

class SalesQuoteSaveAfterObserverTest extends TestCase
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var MerchantBusinessService
     */
    private $merchantBusinessService;
    /**
     * @var SalesQuoteSaveAfterObserver
     */
    private $salesQuoteSaveAfterObserver;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->merchantBusinessService = $this->createMock(MerchantBusinessService::class);
        $this->salesQuoteSaveAfterObserver = new SalesQuoteSaveAfterObserver(
            $this->logger,
            $this->merchantBusinessService
        );
    }

    public function testExecuteCartInitiatedNotificationNotSend()
    {
        $observer = $this->createMock(Observer::class);
        $quote = $this->createMock(Quote::class);
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('quote')->willReturn($quote);
        $observer->method('getEvent')->willReturn($event);
        $quote->method('getId')->willReturn(42);
        $this->merchantBusinessService
            ->expects($this->once())
            ->method('isSendCartInitiatedNotification')
            ->with($quote)
            ->willReturn(false);
        $this->merchantBusinessService
            ->expects($this->once())
            ->method('createAndSendCartInitiatedBusinessEvent')
            ->with($quote);

        $this->salesQuoteSaveAfterObserver->execute($observer);
    }

    public function testExecuteCartInitiatedNotificationAlreadySend()
    {
        $observer = $this->createMock(Observer::class);
        $quote = $this->createMock(Quote::class);
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('quote')->willReturn($quote);
        $observer->method('getEvent')->willReturn($event);
        $quote->method('getId')->willReturn(42);
        $this->merchantBusinessService
            ->expects($this->once())
            ->method('isSendCartInitiatedNotification')
            ->with($quote)
            ->willReturn(true);

        $this->merchantBusinessService
            ->expects($this->never())
            ->method('createAndSendCartInitiatedBusinessEvent')
            ->with($quote);

        $this->salesQuoteSaveAfterObserver->execute($observer);
    }

}
