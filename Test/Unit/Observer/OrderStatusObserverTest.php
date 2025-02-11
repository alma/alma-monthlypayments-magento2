<?php

namespace Alma\MonthlyPayments\Test\Unit\Observer;

use Alma\API\Client;
use Alma\API\Endpoints\Orders;
use Alma\API\Endpoints\Payments;
use Alma\API\Entities\Order;
use Alma\API\Entities\Payment;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Observer\OrderStatusObserver;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;

class OrderStatusObserverTest extends TestCase
{
    private $logger;
    private $observer;

    private $event;
    private $paymentsEndpoint;
    private $almaClient;

    protected function setUp(): void
    {
        $this->paymentsEndpoint = $this->createMock(Payments::class);

        $client = $this->createMock(Client::class);
        $client->payments = $this->paymentsEndpoint;

        $this->logger = $this->createMock(Logger::class);
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->almaClient->method('getDefaultClient')->willReturn($client);
        $this->observer = $this->createMock(Observer::class);
        $this->event = $this->createMock(Event::class);

        $this->observer->method('getEvent')->willReturn($this->event);
    }

    private function createOrderStatusObserverObject(): OrderStatusObserver
    {
        return new OrderStatusObserver(
            $this->logger,
            $this->almaClient
        );
    }

    public function testGivenOrderStateIsNewShouldReturnVoid(): void
    {
        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $order->method('getState')->willReturn('new');
        $orderStatusObserver = $this->createOrderStatusObserverObject();
        $this->event->method('getData')->willReturn($order);
        $this->paymentsEndpoint->expects($this->never())->method('addOrderStatusByMerchantOrderReference');
        $orderStatusObserver->execute($this->observer);
    }

    public function testGivenProcessingOrderWithNonAlmaPaymentMethodShouldNotCallAlmaAndReturnVoid(): void
    {
        $payment = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $payment->method('getMethod')->willReturn('not_alma');

        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $order->method('getState')->willReturn('processing');
        $order->method('getPayment')->willReturn($payment);

        $orderStatusObserver = $this->createOrderStatusObserverObject();
        $this->event->method('getData')->willReturn($order);
        $this->paymentsEndpoint->expects($this->never())->method('addOrderStatusByMerchantOrderReference');
        $orderStatusObserver->execute($this->observer);
    }

    public function testGivenNoAlmaPaymentIdInAdditionalInformationMustReturnWithoutCallAddOrderStatusByMerchantOrderReference(): void
    {
        $payment = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $payment->method('getMethod')->willReturn(Config::CODE);
        $payment->method('getAdditionalInformation')->willReturn([]);

        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $order->method('getState')->willReturn('processing');
        $order->method('getStatus')->willReturn('processing_status');
        $order->method('getPayment')->willReturn($payment);

        $this->event->method('getData')->willReturn($order);

        $this->paymentsEndpoint->expects($this->never())->method('addOrderStatusByMerchantOrderReference');
        $this->createOrderStatusObserverObject()->execute($this->observer);

    }


    /**
     * @dataProvider orderStatusDataProvider
     */
    public function testGivenProcessingOrderWithAlmaPaymentMethodShouldCallAlmaSendStatusAndReturnVoid($dataProvider): void
    {
        $payment = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $payment->method('getMethod')->willReturn(Config::CODE);
        $payment->method('getAdditionalInformation')->willReturn([Config::ORDER_PAYMENT_ID => 'alma_payment_external_id']);

        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $order->method('getState')->willReturn('processing');
        $order->method('getStatus')->willReturn($dataProvider['status']);
        $order->method('getPayment')->willReturn($payment);
        $order->method('hasShipments')->willReturn($dataProvider['is_shipped']);
        $order->method('getIncrementId')->willReturn('100000013');

        $this->event->method('getData')->willReturn($order);

        $this->paymentsEndpoint->expects($this->never())->method('fetch');
        $this->paymentsEndpoint
            ->expects($this->once())
            ->method('addOrderStatusByMerchantOrderReference')
            ->with(
                'alma_payment_external_id',
                '100000013',
                $dataProvider['status'],
                $dataProvider['is_shipped']

            );
        $this->createOrderStatusObserverObject()->execute($this->observer);
    }

    private function orderStatusDataProvider(): array
    {
        return [
            'Test with an empty status' => [
                [
                    'status' => null,
                    'is_shipped' => false,
                ]
            ],
            'Test with an string status' => [
                [
                    'status' => 'status_pending',
                    'is_shipped' => true,
                ]
            ],
        ];
    }
}
