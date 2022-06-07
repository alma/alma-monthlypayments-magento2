<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Helpers\OrderHelper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Helper\Context;

class OrderHelperTest extends TestCase
{
    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepository;
    /**
     * @var OrderFactory|MockObject
     */
    private $orderFactory;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->orderFactory = $this->createMock(OrderFactory::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->orderManagement = $this->createMock(OrderManagementInterface::class);
    }

    public function testInstanceConfigHelper()
    {
        $orderHelper = $this->createNewOrderHelper();
        $this->assertInstanceOf(OrderHelper::class, $orderHelper);
    }

    public function testImplementAbstractHelper()
    {
        $orderHelper = $this->createNewOrderHelper();
        $this->assertInstanceOf(AbstractHelper::class, $orderHelper);
    }

    public function testCancelOrderUseOrderId()
    {
        $mockOrderId = 10;
        $this->orderManagement->expects($this->once())
            ->method('cancel')
            ->with($mockOrderId);
        $orderHelper = $this->createNewOrderHelper();
        $orderHelper->cancelOrderById($mockOrderId);
    }
    public function testNotifyOrderUseOrderId()
    {
        $mockOrderId = 10;
        $this->orderManagement->expects($this->once())
            ->method('notify')
            ->with($mockOrderId);
        $orderHelper = $this->createNewOrderHelper();
        $orderHelper->notifyOrderById($mockOrderId);
    }

    public function testSaveMethodStructure(): void
    {
        $mockOrder = $this->createMock(Order::class);
        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($mockOrder);
        $orderHelper = $this->createNewOrderHelper();
        $orderHelper->saveOrderInRepository($mockOrder);
    }

    private function createNewOrderHelper(): OrderHelper
    {
        return new OrderHelper($this->contextMock, $this->orderFactory, $this->orderRepository, $this->orderManagement);
    }

}
