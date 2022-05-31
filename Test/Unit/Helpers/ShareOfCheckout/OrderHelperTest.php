<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers\ShareOfCheckout;

use Alma\MonthlyPayments\Helpers\OrderHelper as GlobalOrderHelper;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\DateHelper;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\OrderHelper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use PHPUnit\Framework\TestCase;
use Magento\Sales\Model\ResourceModel\Order\Collection;

class OrderHelperTest extends TestCase
{
    const EURO_CURRENCY = 'EUR';

    public function setUp(): void
    {
        $context = $this->createMock(Context::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $orderHelper = $this->createMock(GlobalOrderHelper::class);
        $dateHelper = $this->createMock(DateHelper::class);
        $this->orderHelper = new OrderHelper($context, $this->collectionFactory, $orderHelper, $dateHelper);
    }

    public function testInstancePayloadBuilder(): void
    {
        $this->assertInstanceOf(OrderHelper::class, $this->orderHelper);
    }

    public function testImplementAbstractHelperInterface(): void
    {
        $this->assertInstanceOf(AbstractHelper::class, $this->orderHelper);
    }

    public function testInitTotalOrderResultFormat(): void
    {
        $expectedResult = [
            'total_order_count' => 0,
            'total_amount' => 0,
            'currency' => 'EUR',
            ];
        $this->assertEquals($expectedResult, $this->orderHelper->initTotalOrderResult(self::EURO_CURRENCY));
    }

    public function testInitOrderResultFormat(): void
    {
        $expectedResult = [
            'order_count' => 0,
            'amount' => 0,
            'currency' => 'EUR',
        ];
        $this->assertEquals($expectedResult, $this->orderHelper->initOrderResult(self::EURO_CURRENCY));
    }
    public function testCreateOrderCollectionParams(): void
    {
        $mockCollection = $this->createMock(Collection::class);

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockCollection);
        $mockCollection->expects($this->once())
            ->method('addAttributeToSelect')
            ->with('*')
            ->willReturnSelf();
        $mockCollection->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['created_at', ['from' => [''], 'to' => ['']]],
                ['state', ['in' => ['processing', 'complete']]]
            )
            ->willReturnSelf();
        $this->orderHelper->createOrderCollection();
    }

    public function testGerOrderCollection(): void
    {
        $mockOrderSearchResultInterface = $this->createMock(OrderSearchResultInterface::class);
        $this->orderHelper->setOrderCollection($mockOrderSearchResultInterface);
        $this->assertEquals($mockOrderSearchResultInterface, $this->orderHelper->getOrderCollection());
    }

}
