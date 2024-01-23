<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Insurance;

use Alma\MonthlyPayments\Model\Insurance\Subscription;
use Alma\MonthlyPayments\Model\Insurance\SubscriptionFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{

    const ORDER_ID = 123;
    const SUBSCRIPTION_ID = 'subscription_123';
    const SUBSCRIPTION_PRICE = 12300;
    const CONTRACT_ID = 'contract_1234';
    const CMS_REFERENCE = 'M24-045';
    const SUBSCRIPTION_STATE = 'Active';
    const SUBSCRIPTION_MODE = 'Test';
    const CANCELLATION_DATE = '2024:09:01-00:00:00';
    const CANCELLATION_REASON = 'Receive to late';
    const IS_REFUNDED = false;
    /**
     * @var Subscription
     */
    private $subscription;

    protected function setUp(): void
    {
        $this->subscription = (new ObjectManager($this))->getObject(Subscription::class, []);
        $this->subscription->setOrderId(self::ORDER_ID);
        $this->subscription->setSubscriptionId(self::SUBSCRIPTION_ID);
        $this->subscription->setSubscriptionPrice(self::SUBSCRIPTION_PRICE);
        $this->subscription->setContractId(self::CONTRACT_ID);
        $this->subscription->setCmsReference(self::CMS_REFERENCE);
        $this->subscription->setSubscriptionState(self::SUBSCRIPTION_STATE);
        $this->subscription->setSubscriptionMode(self::SUBSCRIPTION_MODE);
        $this->subscription->setCancellationDate(self::CANCELLATION_DATE);
        $this->subscription->setCancellationReason(self::CANCELLATION_REASON);
        $this->subscription->setIsRefunded(self::IS_REFUNDED);
    }

    public function testGetterAndSetters(): void
    {
        $this->assertEquals(self::SUBSCRIPTION_ID, $this->subscription->getSubscriptionId());
        $this->assertEquals(self::ORDER_ID, $this->subscription->getOrderId());
        $this->assertEquals(self::SUBSCRIPTION_PRICE, $this->subscription->getSubscriptionPrice());
        $this->assertEquals(self::CONTRACT_ID, $this->subscription->getContractId());
        $this->assertEquals(self::CMS_REFERENCE, $this->subscription->getCmsReference());
        $this->assertEquals(self::SUBSCRIPTION_STATE, $this->subscription->getSubscriptionState());
        $this->assertEquals(self::SUBSCRIPTION_MODE, $this->subscription->getSubscriptionMode());
        $this->assertEquals(self::CANCELLATION_DATE, $this->subscription->getCancellationDate());
        $this->assertEquals(self::CANCELLATION_REASON, $this->subscription->getCancellationReason());
        $this->assertEquals(self::IS_REFUNDED, $this->subscription->getIsRefunded());
    }
}
