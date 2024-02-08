<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Request\WebsiteCustomerDetailsDataBuilder;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\OrderHelper;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Reports\Model\ResourceModel\Customer\Orders\Collection;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;

class WebsiteCustomerDetailsDataBuilderTest extends TestCase
{
    const INCREMENT_ID =  '100001';

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var OrderHelper|(OrderHelper&object&\PHPUnit\Framework\MockObject\MockObject)|(OrderHelper&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderHelper;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->orderHelper = $this->createMock(OrderHelper::class);
    }

    /**
     * @return Logger[]
     */
    private function getConstructorDependency(): array
    {
        return [
            $this->logger,
            $this->orderHelper
        ];
    }

    /**
     * @return WebsiteCustomerDetailsDataBuilder
     */
    private function createWebsiteCustomerDetailsDataBuilderTest(): WebsiteCustomerDetailsDataBuilder
    {
        return new WebsiteCustomerDetailsDataBuilder(...$this->getConstructorDependency());
    }

    /**
     * @dataProvider payloadDataProvider
     * @param array $customer
     * @param Collection $orderCollection
     * @param array $previousOrders
     * @param bool $isGuest
     * @return void
     */
    public function testWebsiteCustomerDetailsPayload(array $customer, Collection $orderCollection, array $previousOrders, bool $isGuest):void
    {
        //mock orders
        $this->orderHelper->method('getValidOrderCollectionByCustomerId')->willReturn($orderCollection);
        $paymentDataBuilder = $this->createWebsiteCustomerDetailsDataBuilderTest()
            ->build($this->mockBuildSubject($customer));
        $this->assertEquals($this->responseBuilder($previousOrders, $isGuest), $paymentDataBuilder);
    }

    /**
     * @param array $previousOrders
     * @param bool $isGuest
     * @return array
     */
    private function responseBuilder(array $previousOrders, bool $isGuest):array
    {
        return [
            'website_customer_details' => [
                'is_guest' => $isGuest,
                'previous_orders' => $previousOrders
            ]
        ];
    }

    /**
     * @param  array $customer
     * @return array
     */
    private function mockBuildSubject(array $customer):array
    {
        $orderInterfaceMock = $this->createMock(OrderAdapterInterface::class);
        $orderInterfaceMock
            ->method('getCustomerId')
            ->willReturn($customer['id']);
        $paymentDataObjectMock = $this->createMock(PaymentDataObject::class);
        $paymentDataObjectMock
            ->method('getOrder')
            ->willReturn($orderInterfaceMock);
        return ['payment' => $paymentDataObjectMock];
    }

    public function payloadDataProvider():array
    {
        return [
            'Previous order must be an empty array for guest' => [
                'customer' => [
                    'id' => null
                ],
                'orderCollection' => $this->mockOrderCollectionFactory(),
                'previousOrders' => [],
                'isGuest' => true
            ],
            'Previous order must be an empty array for identified customer without previous order' => [
                'customer' => [
                    'id' => 1
                ],
                'orderCollection' => $this->mockOrderCollectionFactory(),
                'previousOrders' => [],
                'isGuest' => false
            ],
            'Previous order must be an array for identified customer with previous order' => [
                'customer' => [
                    'id' => 1
                ],
                'orderCollection' => $this->mockOrderCollectionFactory(
                    [
                        $this->mockOrderFactory(123),
                        $this->mockOrderFactory(223)
                    ]
                ),
                'previousOrders' => [
                    [
                        "purchase_amount"=> 12300,
                        "created"=> 1687513900,
                        "items" => [],
                        "payment_method" =>'',
                        "shipping_method" => ''
                    ],
                    [
                        "purchase_amount"=> 22300,
                        "created"=> 1687513900,
                        "items" => [],
                        "payment_method" =>'',
                        "shipping_method" => ''
                    ]
                ],
                'isGuest' => false
            ],
        ];
    }

    /**
     * Get a mock order collection
     *
     * @param array $orders
     * @return Collection
     */
    private function mockOrderCollectionFactory(array $orders = []): Collection
    {
        $emptyIterator = new \ArrayIterator($orders);
        $emptyCollection = $this->createPartialMock(Collection::class, ['getIterator']);
        $emptyCollection->method('getIterator')->willReturn($emptyIterator);
        return $emptyCollection;
    }

    private function mockOrderFactory($total): Order
    {
        $order = $this->createMock(Order::class);
        $order->method('getGrandTotal')->willReturn($total);
        $order->method('getCreatedAt')->willReturn('Fri, 23 Jun 2023 09:51:40 GMT');
        $order->method('getItems')->willReturn([]);
        return $order;
    }
}
