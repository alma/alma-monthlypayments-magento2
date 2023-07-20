<?php

namespace Alma\MonthlyPayments\Test\Unit\Controller\Payment;

use Alma\API\Client;
use Alma\API\Endpoints\Payments;
use Alma\MonthlyPayments\Controller\Payment\CancelInPagePayment;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\OrderHelper;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CancelInPagePaymentTest extends TestCase
{
    private $logger;
    private $jsonFactory;
    private $checkoutSession;
    private $orderHelper;
    private $almaClient;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->jsonFactory = $this->createMock(JsonFactory::class);
        $this->checkoutSession = $this->createMock(Session::class);
        $this->orderHelper = $this->createMock(OrderHelper::class);
        $this->almaClient = $this->createMock(AlmaClient::class);
    }

    private function getConstructorDependency(): array
    {
        return [
            $this->logger,
            $this->jsonFactory,
            $this->checkoutSession,
            $this->orderHelper,
            $this->almaClient,
        ];
    }

    public function testResponseIsAJson():void
    {
        $this->generateJsonResponse();
        $this->assertInstanceOf(Json::class, $this->createCancelInaPgePayment()->execute());
    }

    public function testShouldGetOrderInCheckoutSession():void
    {
        $this->generateJsonResponse();
        $this->generateAlmaClient();
        $this->checkoutSession->expects($this->once())->method('getLastRealOrder')->willReturn($this->createOrder());
        $this->createCancelInaPgePayment()->execute();
    }

    public function testShouldReturnErrorIfNoOrderInSession():void
    {
        $this->generateJsonResponse(true);

        $this->checkoutSession->expects($this->once())->method('getLastRealOrder')->willReturn(null);
        $this->createCancelInaPgePayment()->execute();
    }

    public function testShouldNotCallOrderCancelIfNotCancellableAndReturnError():void
    {
        $this->generateJsonResponse(true);
        $order = $this->createOrder(false);
        $order->expects($this->never())->method('cancel')->willReturn($order);
        $this->checkoutSession->expects($this->once())->method('getLastRealOrder')->willReturn($order);
        $this->createCancelInaPgePayment()->execute();
    }

    public function testShouldCallOrderCancel():void
    {
        $this->generateJsonResponse();
        $this->generateAlmaClient(true);
        $this->checkoutSession->expects($this->once())->method('getLastRealOrder')->willReturn($this->createOrder());
        $this->createCancelInaPgePayment()->execute();
    }

    public function testShouldAddHistoryMessage():void
    {
        $this->generateJsonResponse();
        $this->generateAlmaClient();
        $order = $this->createOrder();
        $order->expects($this->once())->method('addStatusToHistory')->with(Order::STATE_CANCELED);
        $this->checkoutSession->expects($this->once())->method('getLastRealOrder')->willReturn($order);
        $this->createCancelInaPgePayment()->execute();
    }

    public function testShouldRestoreQuote():void
    {
        $this->generateJsonResponse();
        $this->generateAlmaClient();
        $this->checkoutSession->expects($this->once())->method('getLastRealOrder')->willReturn($this->createOrder());
        $this->checkoutSession->expects($this->once())->method('restoreQuote');
        $this->createCancelInaPgePayment()->execute();
    }

    public function testShouldSaveTheOrderRepository():void
    {
        $this->generateJsonResponse();
        $this->generateAlmaClient();
        $this->checkoutSession->expects($this->once())->method('getLastRealOrder')->willReturn($this->createOrder());
        $this->orderHelper->expects($this->once())->method('save');
        $this->createCancelInaPgePayment()->execute();
    }

    public function testShouldCancelAlmaPayment():void
    {
        $this->generateJsonResponse();
        $this->generateAlmaClient(true);
        $this->checkoutSession->expects($this->once())->method('getLastRealOrder')->willReturn($this->createOrder());
        $this->orderHelper->expects($this->once())->method('save');
        $this->createCancelInaPgePayment()->execute();
    }

    private function createCancelInaPgePayment(): CancelInPagePayment
    {
        return new CancelInPagePayment(...$this->getConstructorDependency());
    }

    private function createOrder($canCancel = true):MockObject
    {
        $order = $this->createMock(Order::class);
        $order->method('canCancel')->willReturn($canCancel);
        if ($canCancel) {
            $payment = $this->createMock(OrderPaymentInterface::class);
            $payment->method('getAdditionalInformation')->willReturn([Config::ORDER_PAYMENT_ID => 'payment_id']);
            $order->method('getPayment')->willReturn($payment);
            $order->expects($this->once())->method('cancel')->willReturn($order);
        }

        return $order;
    }

    private function generateJsonResponse($error = false): void
    {
        $jsonMock = $this->createMock(Json::class);
        if ($error) {
            $jsonMock->expects($this->once())->method('setStatusHeader')->with(400);
        }
        $this->jsonFactory->method('create')->willReturn($jsonMock);
    }

    private function generateAlmaClient($cancelPayment = false)
    {
        $endpointPayment = $this->createMock(Payments::class);
        $clientMock = $this->createMock(Client::class);
        $clientMock->payments = $endpointPayment;
        if ($cancelPayment) {
            $clientMock->payments->expects($this->once())->method('cancel');
        }
        $this->almaClient
            ->method('getDefaultClient')
            ->willReturn($clientMock);
    }
}
