<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Request\CartDataBuilder;
use Alma\MonthlyPayments\Gateway\Request\OrderDataBuilder;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\ProductHelper;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollectionAlias;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Item;
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
        $orderInterfaceMock = $this->createMock(OrderAdapterInterface::class);
        $orderInterfaceMock->expects($this->once())->method('getOrderIncrementId')->willReturn(self::INCREMENT_ID);

        $paymentDataObjectMock = $this->createMock(PaymentDataObject::class);
        $paymentDataObjectMock->expects($this->once())->method('getOrder')->willReturn($orderInterfaceMock);
        $buildSubjectMock = ['payment' => $paymentDataObjectMock];

        $paymentDataBuilder = $this->createOrderDataBuilderTest()->build($buildSubjectMock);
        $this->assertEquals($this->responseBuilder(), $paymentDataBuilder);

    }

    private function responseBuilder():array
    {
        return [
            'order' => [
                'merchant_reference' => self::INCREMENT_ID
            ]
        ];
    }
}
