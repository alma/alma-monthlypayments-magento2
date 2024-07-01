<?php

namespace Alma\MonthlyPayments\Test\Unit\Block\Adminhtml\Insurance;

use Alma\MonthlyPayments\Block\Adminhtml\Insurance\SubscriptionDetails;
use Magento\Backend\Model\Url;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\TestCase;

class SubscriptionDetailsTest extends TestCase
{

    /**
     * @var \Alma\MonthlyPayments\Helpers\Logger
     */
    private $logger;
    /**
     * @var \Magento\Backend\Block\Template\Context
     */
    private $context;
    /**
     * @var \Alma\MonthlyPayments\Helpers\InsuranceHelper
     */
    private $insuranceHelper;
    /**
     * @var \Alma\MonthlyPayments\Helpers\ApiConfigHelper
     */
    private $apiConfigHelper;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;
    /**
     * @var \Magento\Directory\Helper\Data
     */
    private $directoryHelper;
    /**
     * @var \Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription\CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;
    private $urlBuilder;

    public function setUp(): void
    {
        $this->logger = $this->createMock(\Alma\MonthlyPayments\Helpers\Logger::class);
        $this->context = $this->createMock(\Magento\Backend\Block\Template\Context::class);
        $request = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $request->method('getParam')->willReturn('1');
        $this->context->method('getRequest')->willReturn($request);
        $this->insuranceHelper = $this->createMock(\Alma\MonthlyPayments\Helpers\InsuranceHelper::class);
        $this->apiConfigHelper = $this->createMock(\Alma\MonthlyPayments\Helpers\ApiConfigHelper::class);
        $this->collectionFactory = $this->createMock(\Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription\CollectionFactory::class);
        $this->urlBuilder = $this->createMock(Url::class);
        $this->orderRepository = $this->createMock(\Magento\Sales\Model\OrderRepository::class);
        $this->jsonHelper = $this->createMock(\Magento\Framework\Json\Helper\Data::class);
        $this->directoryHelper = $this->createMock(\Magento\Directory\Helper\Data::class);
    }

    private function getConstructorDependencies(): array
    {
        return [
            $this->logger,
            $this->context,
            $this->insuranceHelper,
            $this->apiConfigHelper,
            $this->collectionFactory,
            $this->orderRepository,
            $this->urlBuilder,
            [],
            $this->jsonHelper,
            $this->directoryHelper
        ];
    }

    private function createSubscriptionDetails(): SubscriptionDetails
    {
        return new SubscriptionDetails(...$this->getConstructorDependencies());
    }

    public function testGetScriptUrl(): void
    {
        $this->apiConfigHelper->expects($this->once())
            ->method('getActiveMode')
            ->willReturn('activeMode');
        $this->insuranceHelper->expects($this->once())
            ->method('getScriptUrl')
            ->with('activeMode')
            ->willReturn('https://my-iframe-url/displayModal.js');
        $subscriptionDetails = $this->createSubscriptionDetails();
        $this->assertEquals('https://my-iframe-url/displayModal.js', $subscriptionDetails->getScriptUrl());
    }

    public function testGetOrderDatailUrl(): void
    {
        $this->apiConfigHelper->expects($this->once())
            ->method('getActiveMode')
            ->willReturn('activeMode');
        $this->insuranceHelper->expects($this->once())
            ->method('getOrderDetailsUrl')
            ->with('activeMode')
            ->willReturn('https://my-order_details-url/back.js');
        $subscriptionDetails = $this->createSubscriptionDetails();
        $this->assertEquals('https://my-order_details-url/back.js', $subscriptionDetails->getOrderDetailsUrl());
    }

    public function testGetSubscriptionCollectionReturnArrayData(): void
    {
        $collection = $this->createMock(\Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription\Collection::class);
        $collection->method('addFieldToFilter');
        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $collection->method('getSelect')->willReturn($selectMock);
        $collection->method('getData')->willReturn(['subscription_id' => 1]);
        $selectMock->method('joinLeft')->willReturn($collection);
        $this->collectionFactory->method('create')->willReturn($collection);
        $subscriptionDetails = $this->createSubscriptionDetails();
        $data = $subscriptionDetails->getSubscriptionCollection();
        $this->assertSame(['subscription_id' => 1], $data);
    }

    public function testGetActiveModeReturnApiConfigActiveMode(): void
    {
        $this->apiConfigHelper->method('getActiveMode')->willReturn('sandbox');
        $subscriptionDetails = $this->createSubscriptionDetails();
        $this->assertEquals('sandbox', $subscriptionDetails->getActiveMode());
    }

    public function testGetOrderIdReturnRequestParamOrderId(): void
    {
        $subscriptionDetails = $this->createSubscriptionDetails();
        $this->assertEquals('1', $subscriptionDetails->getOrderId());
    }

    public function testGetOrderReturnOrderInterfaceIfOrderExist(): void
    {
        $this->orderRepository->method('get')->willReturn($this->createMock(OrderInterface::class));
        $subscriptionDetails = $this->createSubscriptionDetails();
        $this->assertInstanceOf(OrderInterface::class, $subscriptionDetails->getOrder());
    }

    public function testGetOrderReturnNullIfNoOrderInDb(): void
    {
        $this->orderRepository->method('get')->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());
        $subscriptionDetails = $this->createSubscriptionDetails();
        $this->assertNull($subscriptionDetails->getOrder());
    }

    public function testGetIncrementIdReturnOrderIncrementId(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getIncrementId')->willReturn('000000001');
        $this->orderRepository->method('get')->willReturn($order);
        $subscriptionDetails = $this->createSubscriptionDetails();
        $this->assertEquals('000000001', $subscriptionDetails->getIncrementId());
    }

    public function testGetOrderDatedReturnOrderCreatedAt(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getCreatedAt')->willReturn('06/12/14 12:00:00');
        $this->orderRepository->method('get')->willReturn($order);
        $subscriptionDetails = $this->createSubscriptionDetails();
        $this->assertEquals('06/12/14 12:00:00', $subscriptionDetails->getOrderDate());
    }

    public function testGetCustomerFirstNameReturnOrderFirstname(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getCustomerFirstname')->willReturn('John');
        $this->orderRepository->method('get')->willReturn($order);
        $subscriptionDetails = $this->createSubscriptionDetails();
        $this->assertEquals('John', $subscriptionDetails->getCustomerFirstName());
    }

    public function testGetCustomerLastNameReturnOrderLastName(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getCustomerLastname')->willReturn('Doe');
        $this->orderRepository->method('get')->willReturn($order);
        $subscriptionDetails = $this->createSubscriptionDetails();
        $this->assertEquals('Doe', $subscriptionDetails->getCustomerLastName());
    }
}
