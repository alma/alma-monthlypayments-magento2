<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Gateway\Request\RefundDataBuilder;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderRepository;
use PHPUnit\Framework\TestCase;

class RefundDataBuilderTest extends TestCase
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    public function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
    }

    public function testRefundDataBuilderIsInstanceOffBuilderInterface(): void
    {
        $this->assertInstanceOf(BuilderInterface::class, $this->createNewRefundDataBuilder());
    }

    public function testRefundPayloadStructure(): void
    {
        $mockPaymentId = 'payment_11uNKOn3uuKhgUdY2eU6AZF1oKifmetCKZ';
        $mockMerchantId = 'merchant_11uNKOn3uuKhgUdY2eU6AZF1oKiametCKZ';
        $mockAmount = '75.20';
        $mockTotalRefunded = '100.0000';
        $mockGrandTotal = '1000.0000';
        $mockOrderId = 21;
        $mockStoreId = '2';
        $orderInterfaceMock = $this->createMock(OrderInterface::class);
        $orderInterfaceMock->method('getStoreId')
            ->willReturn($mockStoreId);
        $orderInterfaceMock->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn($mockGrandTotal);
        $orderAdapterInterface = $this->createMock(OrderAdapterInterface::class);
        $orderAdapterInterface->expects($this->once())
            ->method('getId')
            ->willReturn($mockOrderId);
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->with($mockOrderId)
            ->willReturn($orderInterfaceMock);
        $infoPaymentMock = $this->createMock(Payment::class);
        $infoPaymentMock->expects($this->once())
            ->method('getAdditionalInformation')
            ->with(Config::ORDER_PAYMENT_ID)
            ->willReturn($mockPaymentId);
        $infoPaymentMock->expects($this->once())
            ->method('getAmountRefunded')
            ->willReturn($mockTotalRefunded);
        $paymentDataObject = $this->createMock(PaymentDataObject::class);
        $paymentDataObject->expects($this->once())
            ->method('getPayment')
            ->willReturn($infoPaymentMock);
        $paymentDataObject->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderAdapterInterface);
        $buildSubjectMock = [
            'payment' => $paymentDataObject,
            'amount' => $mockAmount
        ];
        $this->config->expects($this->once())
            ->method('getMerchantId')
            ->willReturn($mockMerchantId);
        $refundDataBuilder = $this->createNewRefundDataBuilder();
        $resultMock = [
            'payment_id' => $mockPaymentId,
            'merchant_id' => $mockMerchantId,
            'amount' => $mockAmount,
            'total_refund' => $mockTotalRefunded,
            'order_total' => $mockGrandTotal,
            'store_id' => $mockStoreId
        ];
        $this->assertEquals($resultMock, $refundDataBuilder->build($buildSubjectMock));
    }

    public function testPartialRefundIsNotCountedTwiceWhenOrderAlreadyIncludesIt(): void
    {
        $payload = $this->buildRefundPayload('250.0000', '125.0000', null, '125.00');

        $this->assertNull($payload['total_refund']);
        $this->assertEquals(125, $payload['total_refund'] + $payload['amount']);
        $this->assertNotEquals($payload['order_total'], $payload['total_refund'] + $payload['amount']);
    }

    public function testLastPartialRefundAddsUpToOrderTotal(): void
    {
        $payload = $this->buildRefundPayload('250.0000', '250.0000', '125.0000', '125.00');

        $this->assertEquals(125, $payload['total_refund']);
        $this->assertEquals($payload['order_total'], $payload['total_refund'] + $payload['amount']);
    }

    private function buildRefundPayload(
        string $grandTotal,
        string $orderTotalRefunded,
        ?string $paymentAmountRefunded,
        string $amount
    ): array {
        $orderMock = $this->createStub(OrderInterface::class);
        $orderMock->method('getGrandTotal')->willReturn($grandTotal);
        $orderMock->method('getTotalRefunded')->willReturn($orderTotalRefunded);
        $orderMock->method('getStoreId')->willReturn('1');
        $orderAdapter = $this->createStub(OrderAdapterInterface::class);
        $orderAdapter->method('getId')->willReturn(42);
        $this->orderRepository->method('get')->with(42)->willReturn($orderMock);
        $paymentMock = $this->createStub(Payment::class);
        $paymentMock->method('getAdditionalInformation')->willReturn('payment_id');
        $paymentMock->method('getAmountRefunded')->willReturn($paymentAmountRefunded);
        $paymentDataObject = $this->createStub(PaymentDataObject::class);
        $paymentDataObject->method('getPayment')->willReturn($paymentMock);
        $paymentDataObject->method('getOrder')->willReturn($orderAdapter);

        return $this->createNewRefundDataBuilder()->build([
            'payment' => $paymentDataObject,
            'amount' => $amount
        ]);
    }

    private function createNewRefundDataBuilder(): RefundDataBuilder
    {
        return new RefundDataBuilder(...$this->getConstructorDependency());
    }

    private function getConstructorDependency(): array
    {
        return [
            $this->config,
            $this->orderRepository
        ];
    }
}
