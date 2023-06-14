<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Request\WebsiteCustomerDetailsDataBuilder;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\OrderHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use PHPUnit\Framework\TestCase;

class WebsiteCustomerDetailsDataBuilderTest extends TestCase
{
    const INCREMENT_ID =  '100001';

    /**
     * @var Logger
     */
    private $logger;

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
     * @param array $orders
     * @param array $previousOrders
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testWebsiteCustomerDetailsPayload(array $customer, array $orders, array $previousOrders):void
    {
        $paymentDataBuilder = $this->createWebsiteCustomerDetailsDataBuilderTest()
            ->build($this->mockBuildSubject($customer));
        $this->assertEquals($this->responseBuilder($previousOrders), $paymentDataBuilder);
    }

    /**
     * @param $previousOrders
     * @return array
     */
    private function responseBuilder($previousOrders):array
    {
        return [
            'website_customer_details' => [
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
                'orders' => [],
                'previousOrders' => []
            ],
            'Previous order must be an empty array for identified customer' => [
                'customer' => [
                    'id' => 1
                ],
                'orders' => [],
                'previousOrders' => []
            ],
        ];
    }
}
