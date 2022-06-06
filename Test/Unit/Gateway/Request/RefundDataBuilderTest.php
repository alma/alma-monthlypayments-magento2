<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Gateway\Request\RefundDataBuilder;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\Info;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use PHPUnit\Framework\TestCase;

class RefundDataBuilderTest extends TestCase
{
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
        $mockAmount =  '75.20';
        $mockTotalRefunded = '100.0000';
        $mockGrandTotal = '1000.0000';
        $mockOrderId = 21;
        $orderInterfaceMock = $this->createMock(OrderInterface::class);
        $orderInterfaceMock->expects($this->once())
            ->method('getTotalRefunded')
            ->willReturn($mockTotalRefunded);
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
        $infoPaymentMock = $this->createMock(Info::class);
        $infoPaymentMock->expects($this->once())
            ->method('getAdditionalInformation')
            ->with(Config::ORDER_PAYMENT_ID)
            ->willReturn($mockPaymentId);
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
            'order_total' => $mockGrandTotal
        ];
        $this->assertEquals($resultMock, $refundDataBuilder->build($buildSubjectMock));
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
