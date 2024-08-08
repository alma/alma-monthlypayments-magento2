<?php

namespace Alma\MonthlyPayments\Test\Unit\Observer;

use Alma\API\Client;
use Alma\API\Endpoints\Orders;
use Alma\API\Endpoints\Payments;
use Alma\API\Entities\Order;
use Alma\API\Entities\Payment;
use Alma\API\Exceptions\AlmaException;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Observer\ShipmentTrackObserver;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\OrderRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShipmentTrackObserverTest extends TestCase
{

    private const TRACK_ORDER_INCREMENT_ID = '000000003';
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var Observer
     */
    private $observer;

    /**
     * @var AlmaClient
     */
    private $almaClient;

    /**
     * @var Orders
     */
    private $orders;

    /**
     * @var Payments|MockObject
     */
    private $payment;

    /**
     * @var OrderPaymentInterface
     */
    private $orderPayment;
    /**
     * @var OrderInterface
     */
    private $trackOrder;


    protected function setUp(): void
    {
        $this->orderPayment = $this->createMock(OrderPaymentInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->orders = $this->createMock(Orders::class);
        $this->payment = $this->createMock(Payments::class);
        $this->trackOrder = $this->createMock(OrderInterface::class);
        $this->trackOrder->method('getIncrementId')->willReturn(self::TRACK_ORDER_INCREMENT_ID);
        $track = $this->createMock(Track::class);
        $track->method('getOrderId')->willReturn(42);
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('track')->willReturn($track);
        $this->observer = $this->createMock(Observer::class);
        $this->observer->method('getEvent')->willReturn($event);
    }

    protected function tearDown(): void
    {
        $this->orderPayment = null;
        $this->logger = null;
        $this->orderRepository = null;
        $this->almaClient = null;
        $this->orders = null;
        $this->payment = null;
        $this->trackOrder = null;

    }

    /**
     * @dataProvider  getOrderExceptionDataManager
     */
    public function testGetOrderThrowExceptionCallLogError($exception): void
    {
        $this->orderRepository->method('get')->willThrowException($exception);
        $this->whenTrackObserverIsExecutedThenReturnWithoutErrors();

    }


    public function testDirectReturnForOrderWithoutPayment(): void
    {
        $this->trackOrder->method('getPayment')->willReturn(null);

        $this->givenOrderRepositoryReturnTheOrder();

        $this->whenTrackObserverIsExecutedThenReturnWithoutErrors();

    }

    public function testDirectReturnForOrderWithNonAlmaOrder(): void
    {

        $this->givenNonAnAlmaPayment();
        $this->givenAnOrderWithPayment();
        $this->givenOrderRepositoryReturnTheOrder();

        $this->whenTrackObserverIsExecutedThenReturnWithoutErrors();

    }


    public function testNoPaymentIdInAdditionalInformationDirectReturn(): void
    {
        $this->givenPaymentWithoutPaymentIdInAdditionalInfo();
        $this->givenAnOrderWithPayment();
        $this->givenOrderRepositoryReturnTheOrder();

        $this->expectAddTrackingIsNeverCalled();

        $this->whenTrackObserverIsExecutedThenReturnWithoutErrors();
    }

    public function testAlmaClientThrow(): void
    {
        $this->givenPaymentWithPaymentIdInAdditionalInfo();
        $this->givenAnOrderWithPayment();
        $this->givenOrderRepositoryReturnTheOrder();
        $this->givenAlmaClientThrowAnException();

        $this->expectAddTrackingIsNeverCalled();

        $this->whenTrackObserverIsExecutedThenReturnWithoutErrors();
    }

    public function testAlmaApiFetchPaymentThrowExceptionDirectReturn(): void
    {
        $this->givenPaymentWithPaymentIdInAdditionalInfo();
        $this->givenAnOrderWithPayment();
        $this->givenOrderRepositoryReturnTheOrder();
        $this->givenAlmaClient();

        $this->expectAlmaPaymentFetchThrowExceptionAndCalledWith('payment_123456');
        $this->expectAddOrderIsNeverCalled();
        $this->expectAddTrackingIsNeverCalled();


        $this->whenTrackObserverIsExecutedThenReturnWithoutErrors();

    }

    public function testAlmaApiAddOrderThrowExceptionDirectReturn(): void
    {
        $this->givenPaymentWithPaymentIdInAdditionalInfo();
        $this->givenAnOrderWithPayment();
        $this->givenOrderRepositoryReturnTheOrder();
        $this->givenAlmaClient();

        $this->expectAlmaPaymentFetchReturnAlmaPaymentWithoutOrderAndCalledWith('payment_123456');
        $this->expectAddOrderThrowExceptionAndCalledWith('payment_123456');
        $this->expectAddTrackingIsNeverCalled();


        $this->whenTrackObserverIsExecutedThenReturnWithoutErrors();

    }

    public function testCreateOrderAndCallAddTrackingForAlmaPaymentWithoutOrder(): void
    {
        $this->givenPaymentWithPaymentIdInAdditionalInfo();
        $this->givenAnOrderWithPayment();
        $this->givenOrderRepositoryReturnTheOrder();
        $this->givenAlmaClient();

        $this->expectAlmaPaymentFetchReturnAlmaPaymentWithoutOrderAndCalledWith('payment_123456');
        $this->expectAddOrderReturnAlmaOrderAndCalledWith('payment_123456');
        $this->expectAddTrackingIsCalledWith('order_654987');


        $this->whenTrackObserverIsExecutedThenReturnWithoutErrors();
    }

    public function testCallAddTrackingForAlmaPaymentWithOrder(): void
    {
        $this->givenPaymentWithPaymentIdInAdditionalInfo();
        $this->givenAnOrderWithPayment();
        $this->givenOrderRepositoryReturnTheOrder();
        $this->givenAlmaClient();

        $this->expectFetchIsCalledAndReturnAlmaPaymentWithOrder();
        $this->expectAddTrackingIsCalledWith('order_123456');

        $this->whenTrackObserverIsExecutedThenReturnWithoutErrors();
    }

    public function testWithMultiplesOrderWithMerchantReferenceAddTrackingToTheGoodOrder(): void
    {
        $this->givenPaymentWithPaymentIdInAdditionalInfo();
        $this->givenAnOrderWithPayment();
        $this->givenOrderRepositoryReturnTheOrder();
        $this->givenAlmaClient();

        $this->expectFetchIsCalledAndReturnAlmaPaymentWithMultipleOrdersWithMerchantReference();

        $this->expectAddTrackingIsCalledWith('order_987654321');
        $this->whenTrackObserverIsExecutedThenReturnWithoutErrors();

    }

    public function testWithMultiplesOrderWithoutMerchantReferenceCreateOrderAndAddTracking(): void
    {
        $this->givenPaymentWithPaymentIdInAdditionalInfo();
        $this->givenAnOrderWithPayment();
        $this->givenOrderRepositoryReturnTheOrder();
        $this->givenAlmaClient();

        $this->expectFetchIsCalledAndReturnAlmaPaymentWithMultipleOrdersWithoutMerchantReference();
        $this->expectAddOrderReturnAlmaOrderAndCalledWith('payment_123456');

        $this->expectAddTrackingIsCalledWith('order_654987');
        $this->whenTrackObserverIsExecutedThenReturnWithoutErrors();

    }

    private function expectAddOrderReturnAlmaOrderAndCalledWith($paymentId): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getExternalId')->willReturn('order_654987');
        $order->method('getMerchantReference')->willReturn(self::TRACK_ORDER_INCREMENT_ID);
        $this->payment->expects($this->once())->method('addOrder')->with($paymentId)->willReturn($order);
    }

    private function expectAddOrderThrowExceptionAndCalledWith($paymentId): void
    {
        $this->payment->expects($this->once())->method('addOrder')->with($paymentId)->willThrowException(new AlmaException('Impossible to create Order'));
    }

    private function expectAddOrderIsNeverCalled(): void
    {
        $this->payment->expects($this->never())->method('addOrder');
    }

    private function expectAlmaPaymentFetchThrowExceptionAndCalledWith($paymentId): void
    {
        $this->payment->expects($this->once())->method('fetch')->with($paymentId)->willThrowException(new AlmaException('Impossible to fetch payment'));
    }


    private function expectAlmaPaymentFetchReturnAlmaPaymentWithoutOrderAndCalledWith($paymentId): void
    {
        $almaPaymentWithoutOrder = $this->createMock(Payment::class);
        $almaPaymentWithoutOrder->orders = [];
        $this->payment->expects($this->once())->method('fetch')->with($paymentId)->willReturn($almaPaymentWithoutOrder);
    }

    private function expectFetchIsCalledAndReturnAlmaPaymentWithMultipleOrdersWithMerchantReference(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getExternalId')->willReturn('order_123456');
        $order->method('getMerchantReference')->willReturn('0000000543');
        $order2 = $this->createMock(Order::class);
        $order2->method('getExternalId')->willReturn('order_987654321');
        $order2->method('getMerchantReference')->willReturn(self::TRACK_ORDER_INCREMENT_ID);
        $almaPaymentWithOrder = $this->createMock(Payment::class);
        $almaPaymentWithOrder->orders = [$order, $order2];
        $this->payment->expects($this->once())->method('fetch')->with('payment_123456')->willReturn($almaPaymentWithOrder);
    }

    private function expectFetchIsCalledAndReturnAlmaPaymentWithMultipleOrdersWithoutMerchantReference(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getExternalId')->willReturn('order_123456');
        $order->method('getMerchantReference')->willReturn('0000000543');
        $order2 = $this->createMock(Order::class);
        $order2->method('getExternalId')->willReturn('order_987654321');
        $order2->method('getMerchantReference')->willReturn('0000012312');
        $almaPaymentWithOrder = $this->createMock(Payment::class);
        $almaPaymentWithOrder->orders = [$order, $order2];
        $this->payment->expects($this->once())->method('fetch')->with('payment_123456')->willReturn($almaPaymentWithOrder);
    }

    private function expectFetchIsCalledAndReturnAlmaPaymentWithOrder(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getExternalId')->willReturn('order_123456');
        $order->method('getMerchantReference')->willReturn(self::TRACK_ORDER_INCREMENT_ID);
        $almaPaymentWithOrder = $this->createMock(Payment::class);
        $almaPaymentWithOrder->orders = [$order];
        $this->payment->expects($this->once())->method('fetch')->with('payment_123456')->willReturn($almaPaymentWithOrder);
    }

    private function givenAlmaClient(): void
    {
        $client = $this->createMock(Client::class);
        $client->orders = $this->orders;
        $client->payments = $this->payment;

        $this->almaClient
            ->method('getDefaultClient')
            ->willReturn($client);
    }

    private function givenAlmaClientThrowAnException(): void
    {
        $this->almaClient
            ->method('getDefaultClient')
            ->willThrowException(new AlmaClientException('Error with Alma Client'));
    }

    private function expectAddTrackingIsNeverCalled(): void
    {
        $this->orders->expects($this->never())->method('addTracking');
    }

    private function expectAddTrackingIsCalledWith($orderId): void
    {
        $this->orders->expects($this->once())->method('addTracking')->with($orderId);
    }

    private function givenPaymentWithoutPaymentIdInAdditionalInfo(): void
    {
        $this->orderPayment->method('getAdditionalInformation')->willReturn([]);
    }

    private function givenPaymentWithPaymentIdInAdditionalInfo(): void
    {
        $this->givenPaymentWithAlmaPaymentMethod();
        $this->orderPayment->method('getAdditionalInformation')->willReturn(['PAYMENT_ID' => 'payment_123456']);
    }

    private function givenAnOrderWithPayment(): void
    {
        $this->trackOrder->method('getPayment')->willReturn($this->orderPayment);
    }

    private function givenOrderRepositoryReturnTheOrder(): void
    {
        $this->orderRepository->method('get')->willReturn($this->trackOrder);
    }

    private function givenPaymentWithAlmaPaymentMethod(): void
    {
        $this->orderPayment->method('getMethod')->willReturn(Config::CODE);
    }

    private function givenNonAnAlmaPayment(): void
    {
        $this->orderPayment->method('getMethod')->willReturn('not_alma');
    }

    private function whenTrackObserverIsExecutedThenReturnWithoutErrors(): void
    {
        $this->shipmentTrackObserver = new ShipmentTrackObserver(
            $this->orderRepository,
            $this->almaClient,
            $this->logger
        );
        $this->assertNull($this->shipmentTrackObserver->execute($this->observer));
    }

    /**
     * @return array[]
     */
    protected function getOrderExceptionDataManager(): array
    {
        return [
            'NoSuchEntityException' => [
                'exception' => new NoSuchEntityException(),
            ],
            'InputException' => [
                'exception' => new InputException(),
            ],
        ];
    }


}
