<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceSubscriptionException;
use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription\Collection;
use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription\CollectionFactory;
use Alma\MonthlyPayments\Model\Insurance\Subscription;
use Magento\Framework\App\Helper\Context;
use PHPUnit\Framework\TestCase;

class InsuranceSubscriptionHelperTest extends TestCase
{
    private $subscriptionMock;
    private $collectionMock;
    private $collectionFactory;
    private $context;
    private $subscriptionHelper;

    protected function setUp(): void
    {
        $this->subscriptionMock = $this->createMock(Subscription::class);
        $this->collectionMock = $this->createMock(Collection::class);
        $this->collectionMock->method('addFieldToFilter')->willReturn($this->collectionMock);
        $this->collectionMock->method('getFirstItem')->willReturn($this->subscriptionMock);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->collectionFactory->method('create')->willReturn($this->collectionMock);
        $this->context = $this->createMock(Context::class);
        $this->subscriptionHelper = new \Alma\MonthlyPayments\Helpers\InsuranceSubscriptionHelper($this->collectionFactory, $this->context);
    }

    // Givent a unknown subscription id when getDbSubscription then throw exception
    public function testShouldReturnAValidatorExceptionForAnEmptySubscriptionId()
    {
        $this->expectException(AlmaInsuranceSubscriptionException::class);
        $this->expectExceptionMessage('Subscription not found');
        $this->subscriptionMock->method('getId')->willReturn(null);
        $this->subscriptionHelper->getDbSubscription(1);
    }
    //Given a known subscription id when getDbSubscription then return the subscription
    public function testShouldReturnASubscriptionForAValidSubscriptionId()
    {
        $this->subscriptionMock->method('getId')->willReturn(1);
        $this->assertEquals($this->subscriptionMock, $this->subscriptionHelper->getDbSubscription(1));
    }

    /**
     * @dataProvider keyAndExpectedStatusName
     * @return void
     */
    public function testShouldReturnActiveIfKeyIsStarted($key, $expected)
    {
        $this->assertEquals($expected, $this->subscriptionHelper->getNameStatus($key));
    }

    protected function keyAndExpectedStatusName(): array
    {
        return [
            'Empty' => ['', ''],
            'Invalid status' => ['invalid_status', 'invalid_status'],
            'Active' => ['started', 'Active'],
            'Pending Cancellation' => ['pending_cancellation', 'Pending Cancellation'],
            'Canceled' => ['canceled', 'Canceled'],
            'Pending' => ['pending', 'Pending'],
            'failed' => ['failed', 'Failed'],
        ];
    }
}
