<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Request\CartDataBuilder;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\OrderHelper;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use PHPUnit\Framework\TestCase;

class CartDataBuilderTest extends TestCase
{
    private $logger;
    private $orderHelper;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->orderHelper = $this->createMock(OrderHelper::class);
    }
    private function getConstructorDependency(): array
    {
        return [
                $this->logger,
                $this->orderHelper
            ];
    }

    public function testBuildReturnFormatedPayload():void
    {
        $this->orderHelper->method('formatOrderItems')->willReturn([]);
        $this->assertEquals(
            [
                "cart" => [
                    'items' => []
                ]
            ],
            $this->createCartDataBuilderTest()->build(["payment" => $this->createPaymentDataObject()]));
    }
    private function createPaymentDataObject():PaymentDataObjectInterface
    {
        $orderAdapter = $this->createMock(OrderAdapterInterface::class);
        $orderAdapter->method('getItems')->willReturn([]);

        $paymentDataObject = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDataObject->method('getOrder')->willReturn($orderAdapter);
        return $paymentDataObject;
    }
    private function createCartDataBuilderTest(): CartDataBuilder
    {
        return new CartDataBuilder(...$this->getConstructorDependency());
    }
}
