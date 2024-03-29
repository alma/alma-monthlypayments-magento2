<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Insurance;

use Alma\MonthlyPayments\Model\Insurance\Subscription;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{

    const ORDER_ID = 123;
    const ORDER_ITEM_ID = 456;
    const SUBSCRIPTION_ID = 'subscription_123';
    const BROKER_SUBSCRIPTION_ID = 'provider_subscription_123';
    const BROKER_SUBSCRIPTION_REFERENCE = 'provider_reference_123';
    const SUBSCRIPTION_AMOUNT = 12300;
    const CONTRACT_ID = 'contract_1234';
    const CMS_REFERENCE = 'M24-045';
    const LINKED_PRODUCT_NAME = 'My super product name';
    const LINKED_PRODUCT_PRICE = 5300;
    const SUBSCRIPTION_STATE = 'Active';
    const SUBSCRIPTION_MODE = 'Test';
    const CANCELATION_DATE = '2024:09:01-00:00:00';
    const CANCELATION_REQUEST_DATE = '2024:09:02-00:00:00';
    const CANCELATION_REASON = 'Receive to late';
    const IS_REFUNDED = false;
    const CALLBACK_URL = 'https://www.example.com';


    /**
     * @var Subscription
     */
    private $subscription;

    protected function setUp(): void
    {
        $dateTime = new \DateTime('2024:09:01 00:00:00');
        $this->subscription = (new ObjectManager($this))->getObject(Subscription::class, []);
        $this->subscription->setOrderId(self::ORDER_ID);
        $this->subscription->setOrderItemId(self::ORDER_ITEM_ID);
        $this->subscription->setSubscriptionId(self::SUBSCRIPTION_ID);
        $this->subscription->setSubscriptionBrokerId(self::BROKER_SUBSCRIPTION_ID);
        $this->subscription->setSubscriptionBrokerReference(self::BROKER_SUBSCRIPTION_REFERENCE);
        $this->subscription->setSubscriptionAmount(self::SUBSCRIPTION_AMOUNT);
        $this->subscription->setContractId(self::CONTRACT_ID);
        $this->subscription->setCmsReference(self::CMS_REFERENCE);
        $this->subscription->setLinkedProductName(self::LINKED_PRODUCT_NAME);
        $this->subscription->setLinkedProductPrice(self::LINKED_PRODUCT_PRICE);
        $this->subscription->setSubscriptionState(self::SUBSCRIPTION_STATE);
        $this->subscription->setSubscriptionMode(self::SUBSCRIPTION_MODE);
        $this->subscription->setCancellationDate($dateTime);
        $this->subscription->setCancellationReason(self::CANCELATION_REASON);
        $this->subscription->setCancellationRequestDate($dateTime);
        $this->subscription->setIsRefunded(self::IS_REFUNDED);
        $this->subscription->setCallbackUrl(self::CALLBACK_URL);
    }

    public function testGetterAndSetters(): void
    {
        $this->assertEquals(self::ORDER_ID, $this->subscription->getOrderId());
        $this->assertEquals(self::ORDER_ITEM_ID, $this->subscription->getOrderItemId());
        $this->assertEquals(self::SUBSCRIPTION_ID, $this->subscription->getSubscriptionId());
        $this->assertEquals(self::BROKER_SUBSCRIPTION_ID, $this->subscription->getSubscriptionBrokerId());
        $this->assertEquals(self::BROKER_SUBSCRIPTION_REFERENCE, $this->subscription->getSubscriptionBrokerReference());
        $this->assertEquals(self::SUBSCRIPTION_AMOUNT, $this->subscription->getSubscriptionAmount());
        $this->assertEquals(self::CONTRACT_ID, $this->subscription->getContractId());
        $this->assertEquals(self::CMS_REFERENCE, $this->subscription->getCmsReference());
        $this->assertEquals(self::LINKED_PRODUCT_NAME, $this->subscription->getLinkedProductName());
        $this->assertEquals(self::LINKED_PRODUCT_PRICE, $this->subscription->getLinkedProductPrice());
        $this->assertEquals(self::SUBSCRIPTION_STATE, $this->subscription->getSubscriptionState());
        $this->assertEquals(self::SUBSCRIPTION_MODE, $this->subscription->getSubscriptionMode());
        $this->assertEquals(self::CANCELATION_REASON, $this->subscription->getCancellationReason());
        $this->assertEquals(self::CALLBACK_URL, $this->subscription->getCallbackUrl());
        $this->assertEquals(self::IS_REFUNDED, $this->subscription->getIsRefunded());
    }
}
