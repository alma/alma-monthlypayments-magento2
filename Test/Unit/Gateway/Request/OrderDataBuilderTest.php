<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Request\OrderDataBuilder;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use PHPUnit\Framework\TestCase;

class OrderDataBuilderTest extends TestCase
{
    const INCREMENT_ID =  '100001';

    /**
     * @var Logger
     */
    private $logger;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
    }

    private function getConstructorDependency(): array
    {
        return [
            $this->logger,
        ];
    }

    private function createOrderDataBuilderTest(): OrderDataBuilder
    {
        return new OrderDataBuilder(...$this->getConstructorDependency());
    }

    public function testOrderPayload():void
    {
        $paymentDataBuilder = $this->createOrderDataBuilderTest()->build($this->mockBuildSubject());
        $this->assertEquals($this->responseBuilder(), $paymentDataBuilder);
    }

    /**
     * @param string $incrementID
     * @return array
     */
    private function responseBuilder():array
    {
        return [
            'order' => [
                'merchant_reference' => self::INCREMENT_ID
            ]
        ];
    }

    /**
     * @return array
     */
    private function mockBuildSubject():array
    {
        $orderInterfaceMock = $this->createMock(OrderAdapterInterface::class);
        $orderInterfaceMock->expects($this->once())->method('getOrderIncrementId')->willReturn(self::INCREMENT_ID);

        $paymentDataObjectMock = $this->createMock(PaymentDataObject::class);
        $paymentDataObjectMock->expects($this->once())->method('getOrder')->willReturn($orderInterfaceMock);
        return ['payment' => $paymentDataObjectMock];
    }
}
