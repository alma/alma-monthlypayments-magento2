<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Gateway\Request\RefundDataBuilder;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\Info;
use PHPUnit\Framework\TestCase;

class RefundDataBuilderTest extends TestCase
{
    public function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
    }

    public function testRefundDataBuilderIsInstanceOffBuilderInterface(): void
    {
        $this->assertInstanceOf(BuilderInterface::class, $this->createNewRefundDataBuilder());
    }

    public function testRefundPayloadStructure(): void
    {
        $mockPaymentId = 'payment_11uNKOn3uuKhgUdY2eU6AZF1oKifmetCKZ';
        $mockMerchantId = 'merchant_11uNKOn3uuKhgUdY2eU6AZF1oKiametCKZ';
        $mockAmount =  '1021.2';
        $infoPaymentMock = $this->createMock(Info::class);
        $infoPaymentMock->expects($this->once())
            ->method('getAdditionalInformation')
            ->with(Config::ORDER_PAYMENT_ID)
            ->willReturn($mockPaymentId);
        $buildSubjectMock = [
            'payment' => $infoPaymentMock,
            'amount' => $mockAmount
        ];
        $this->config->expects($this->once())
            ->method('getMerchantId')
            ->willReturn($mockMerchantId);
        $refundDataBuilder = $this->createNewRefundDataBuilder();

        $resultMock = [
            'payment_id' => $mockPaymentId,
            'merchant_id' => $mockMerchantId,
            'amount' => $mockAmount
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
            $this->config
        ];
    }
}
