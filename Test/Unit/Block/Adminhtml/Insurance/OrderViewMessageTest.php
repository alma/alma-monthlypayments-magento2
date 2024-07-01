<?php

namespace Alma\MonthlyPayments\Test\Unit\Block\Adminhtml\Insurance;

use Alma\MonthlyPayments\Block\Adminhtml\Insurance\OrderViewMessage;
use Alma\MonthlyPayments\Helpers\InsuranceSubscriptionHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Url;
use PHPUnit\Framework\TestCase;

class OrderViewMessageTest extends TestCase
{

    private $logger;
    private $insuranceSubscriptionHelper;
    private $context;
    private $jsonHelper;
    private $directoryHelper;
    private $url;

    protected function tearDown(): void
    {
        $this->logger = null;
        $this->insuranceSubscriptionHelper = null;
        $this->context = null;
        $this->jsonHelper = null;
        $this->directoryHelper = null;
        $this->url = null;
    }

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->insuranceSubscriptionHelper = $this->createMock(InsuranceSubscriptionHelper::class);

        $requestInterface = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $requestInterface->method('getParam')->willReturn('42');
        $this->context = $this->createMock(Context::class);
        $this->context->method('getRequest')->willReturn($requestInterface);

        $this->url = $this->createMock(Url::class);
        $this->jsonHelper = $this->createMock(\Magento\Framework\Json\Helper\Data::class);
        $this->directoryHelper = $this->createMock(\Magento\Directory\Helper\Data::class);
    }

    private function createOrderViewMessage(): OrderViewMessage
    {
        return new OrderViewMessage(
            $this->logger,
            $this->insuranceSubscriptionHelper,
            $this->context,
            $this->url,
            []
        );
    }


    /**
     * Given request no subscription is found
     * @return void
     */
    public function testShouldReturnFalseWhenNoSubscriptionIsFound(): void
    {
        $this->insuranceSubscriptionHelper->method('getCollectionSubscriptionsByOrderId')->willReturn([]);
        $orderViewMessage = $this->createOrderViewMessage();
        $this->assertFalse($orderViewMessage->hasActiveInsurance());
    }


    /**
     * Given request subscription are found with "subscription_state" inactive
     * @dataProvider inactiveStatusDataProvider
     * @param $inactiveStatus
     * @return void
     */
    public function testShouldReturnFalseWhenSubscriptionIsFoundWithAnInactiveStatus($inactiveStatus): void
    {
        $this->insuranceSubscriptionHelper->method('getCollectionSubscriptionsByOrderId')->willReturn([['entity_id' => 42, 'subscription_state' => $inactiveStatus]]);
        $orderViewMessage = $this->createOrderViewMessage();
        $this->assertFalse($orderViewMessage->hasActiveInsurance());
    }

    /**
     * Given request subscription are found with "subscription_state" active
     * @dataProvider activeStatusDataProvider
     * @param $activeStatus
     * @return void
     */
    public function testShouldReturnTrueWhenSubscriptionIsFoundWithAnActiveStatus($activeStatus): void
    {
        $this->insuranceSubscriptionHelper->method('getCollectionSubscriptionsByOrderId')->willReturn([['entity_id' => 42, 'subscription_state' => $activeStatus]]);
        $orderViewMessage = $this->createOrderViewMessage();
        $this->assertTrue($orderViewMessage->hasActiveInsurance());
    }

    /**
     * Given request subscription are found with 2 subscription with at least on active "subscription_state"
     * @dataProvider mixedStatusWithOneActiveDataProvider
     * @param $firstState
     * @param $secondState
     * @return void
     */
    public function testShouldReturnTrueIfAtLeast1StateIsInActiveList($firstState, $secondState): void
    {
        $this->insuranceSubscriptionHelper->method('getCollectionSubscriptionsByOrderId')->willReturn([['entity_id' => 42, 'subscription_state' => $firstState], ['entity_id' => 43, 'subscription_state' => $secondState]]);
        $orderViewMessage = $this->createOrderViewMessage();
        $this->assertTrue($orderViewMessage->hasActiveInsurance());
    }

    /**
     * Given request subscription are found with 2 subscription with all inactive "subscription_state"
     * @return void
     */
    public function testShouldReturnFalseForAllInactiveSubscriptionState(): void
    {
        $this->insuranceSubscriptionHelper->method('getCollectionSubscriptionsByOrderId')->willReturn([['entity_id' => 42, 'subscription_state' => 'refunded'], ['entity_id' => 43, 'subscription_state' => 'canceled']]);
        $orderViewMessage = $this->createOrderViewMessage();
        $this->assertFalse($orderViewMessage->hasActiveInsurance());
    }

    private function activeStatusDataProvider(): array
    {
        return [
            'Started' => ['started'],
            'Pending Cancellation' => ['pending_cancellation'],
            'Pending' => ['pending'],
        ];
    }

    private function inactiveStatusDataProvider(): array
    {
        return [
            'failed' => ['failed'],
            'canceled' => ['canceled'],
            'Refunded' => ['refunded'],
        ];
    }
    private function mixedStatusWithOneActiveDataProvider(): array
    {
        return [
            'started & failed' => ['started', 'failed'],
            'failed & started' => ['failed', 'started'],
            'Pending Cancellation & refunded' => ['pending_cancellation', 'refunded'],
            'Canceled & pending' => ['canceled', 'pending'],
        ];
    }

    public function testGetDetailUrlIsCalledWithGoodParams():void
    {
        $this->url->expects($this->once())->method('getUrl')->with('alma_monthly/insurance/subscriptiondetails', ['order_id' => '42'])->willReturn('https://mywebsite/alma_monthly/insurance/subscriptiondetails/order_id/42');
        $this->createOrderViewMessage()->getOrderDetailsLink();
    }

}
