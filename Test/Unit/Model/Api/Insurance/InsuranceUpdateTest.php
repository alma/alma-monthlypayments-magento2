<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Api\Insurance;

use Alma\API\Client;
use Alma\API\Endpoints\Insurance;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\InsuranceSubscriptionHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Api\Insurance\InsuranceUpdate;
use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription;
use Magento\Framework\Validator\Exception;
use Magento\Framework\Webapi\Rest\Request;
use PHPUnit\Framework\TestCase;

class InsuranceUpdateTest extends TestCase
{

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Request
     */
    private $request;

    private $almaClient;

    private $subscriptionResourceModel;
    private $insuranceSubscriptionHelper;
    private $notifierPool;
    private $orderRepository;
    private $url;
    private $dbSubscriptionMock;

    private $client;
    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->request = $this->createMock(\Magento\Framework\Webapi\Rest\Request::class);
        $this->logger = $this->createMock(\Alma\MonthlyPayments\Helpers\Logger::class);
        $this->client = $this->createMock(Client::class);
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->dbSubscriptionMock = $this->createMock(\Alma\MonthlyPayments\Model\Insurance\Subscription::class);
        $this->insuranceSubscriptionHelper = $this->createMock(InsuranceSubscriptionHelper::class);
        $this->insuranceSubscriptionHelper->method('getDbSubscription')->willReturn($this->dbSubscriptionMock);
        $this->subscriptionResourceModel = $this->createMock(Subscription::class);
        $this->notifierPool = $this->createMock(\Magento\Framework\Notification\NotifierPool::class);
        $this->orderRepository = $this->createMock(\Magento\Sales\Model\OrderRepository::class);
        $this->url = $this->createMock(\Magento\Backend\Model\Url::class);
        $this->request
            ->method('getParams')
            ->willReturn(['subscription_id' => 'valid_subscription_key']);
    }
    protected function tearDown(): void
    {
        $this->dbSubscriptionMock = null;
        $this->request = null;
    }

    /**
     * @return array[]
     */
    public function getConstructorDependencies(): array
    {
        return [
            $this->request,
            $this->logger,
            $this->almaClient,
            $this->insuranceSubscriptionHelper,
            $this->subscriptionResourceModel,
            $this->notifierPool,
            $this->orderRepository,
            $this->url
        ];
    }

    /**
     * @return InsuranceUpdate
     */
    private function createInstance(): InsuranceUpdate
    {
        return new \Alma\MonthlyPayments\Model\Api\Insurance\InsuranceUpdate(
            ...$this->getConstructorDependencies()
        );
    }

    public function testGivenInvalidSubscriptionIdKeyMustReturnError(): void
    {
        $this->request = null;
        $this->request = $this->createMock(\Magento\Framework\Webapi\Rest\Request::class);
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn(['sid' => 'invalid_subscription_key']);

        $this->expectException(\Magento\Framework\Webapi\Exception::class);
        $instance = $this->createInstance();
        $instance->update();
    }

    public function testGivenSubscriptionIdReturnApiErrorMustReturnError(): void
    {

        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint->expects($this->once())->method('getSubscription')->willThrowException(new \Alma\API\Exceptions\AlmaException('error'));
        $this->client->insurance = $insuranceEndpoint;
        $this->almaClient->method('getDefaultClient')->willReturn($this->client);
        $this->expectException(\Magento\Framework\Webapi\Exception::class);
        $instance = $this->createInstance();
        $instance->update();
    }

    public function testGivenSubscriptionIdReturnEmptySubscriptionListMustReturnError(): void
    {

        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint->expects($this->once())->method('getSubscription')->willReturn(
            ['subscriptions' => []]
        );
        $this->client->insurance = $insuranceEndpoint;
        $this->almaClient->method('getDefaultClient')->willReturn($this->client);
        $this->expectException(\Magento\Framework\Webapi\Exception::class);
        $instance = $this->createInstance();
        $instance->update();
    }

    public function testGivenEmptyDbSubscriptionMustThrow(): void
    {
        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint->expects($this->once())->method('getSubscription')->with(['id' => 'valid_subscription_key'])->willReturn($this->getSubscriptionResultData());
        $this->client->insurance = $insuranceEndpoint;
        $this->almaClient->method('getDefaultClient')->willReturn($this->client);
        $this->insuranceSubscriptionHelper->method('getDbSubscription')->willThrowException(new Exception(__('Subscription not found')));
        $this->expectException(\Magento\Framework\Webapi\Exception::class);
        $instance = $this->createInstance();
        $instance->update();
    }

    public function testGivenAValidApiReturnStartedMustLoadAndSaveModelFromRepositoryAndNotSendNotification(): void
    {
        $apiResult = $this->getSubscriptionResultData();
        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint
            ->expects($this->once())
            ->method('getSubscription')
            ->with(['id' => 'valid_subscription_key'])
            ->willReturn($apiResult);
        $this->client->insurance = $insuranceEndpoint;

        $this->almaClient->method('getDefaultClient')->willReturn($this->client);
        $this->dbSubscriptionMock->method('getId')->willReturn(1);
        $this->subscriptionResourceModel->expects($this->once())->method('save')->with($this->dbSubscriptionMock);
        $this->orderRepository->expects($this->never())->method('get');
        $this->notifierPool->expects($this->never())->method('addMajor');

        $instance = $this->createInstance();
        $this->assertNull($instance->update());
    }

    public function testGivenAValidApiReturnCancelledMustLoadAndSaveModelFromRepositoryAndSendNotification(): void
    {
        $apiResult = $this->getSubscriptionResultData(\Alma\API\Entities\Insurance\Subscription::STATE_CANCELLED);
        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint
            ->expects($this->once())
            ->method('getSubscription')
            ->with(['id' => 'valid_subscription_key'])
            ->willReturn($apiResult);
        $this->client->insurance = $insuranceEndpoint;

        $this->almaClient->method('getDefaultClient')->willReturn($this->client);
        $this->dbSubscriptionMock->expects($this->once())->method('setSubscriptionState')->with($apiResult['subscriptions'][0]['state']);
        $this->dbSubscriptionMock->expects($this->once())->method('setSubscriptionBrokerId')->with($apiResult['subscriptions'][0]['broker_subscription_id']);
        $this->dbSubscriptionMock->expects($this->once())->method('setCancellationDate');
        $this->subscriptionResourceModel->expects($this->once())->method('save')->with($this->dbSubscriptionMock);

        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $orderMock->method('getIncrementId')->willReturn('123');

        $this->orderRepository->expects($this->once())->method('get')->willReturn($orderMock);
        $this->notifierPool->expects($this->once())->method('addMajor');

        $instance = $this->createInstance();
        $this->assertNull($instance->update());
    }
    public function testGivenSubscriptionIsAlreadyCancelledWithACancellationDateNoCallSetCancellationDate():void
    {
        $apiResult = $this->getSubscriptionResultData(\Alma\API\Entities\Insurance\Subscription::STATE_CANCELLED);
        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint
            ->expects($this->once())
            ->method('getSubscription')
            ->with(['id' => 'valid_subscription_key'])
            ->willReturn($apiResult);
        $this->client->insurance = $insuranceEndpoint;
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $orderMock->method('getIncrementId')->willReturn('123');

        $this->orderRepository->method('get')->willReturn($orderMock);

        $this->dbSubscriptionMock->method('getCancellationDate')->willReturn(new \DateTime());
        $this->almaClient->method('getDefaultClient')->willReturn($this->client);
        $this->dbSubscriptionMock->expects($this->never())->method('setCancellationDate');
        $instance = $this->createInstance();
        $this->assertNull($instance->update());

    }


    private function getSubscriptionResultData(string $state = 'started '): array
    {
        return [
            "subscriptions" => [
                [
                    "amount" => 1312,
                    "cms_reference" => "19",
                    "contract_id" => "insurance_contract_755vkKQUnezKPvzMWc4Qeq",
                    "id" => "subscription_5FM3wla3WvVjFaOUb06nXt",
                    "broker_subscription_id" => "xxxx",
                    "state" => $state,
                    "subscriber" => [
                        "address_line_1" => "adr1",
                        "address_line_2" => "adr1",
                        "city" => "adr1",
                        "country" => "adr1",
                        "email" => "mathis.dupuy@almapay.com",
                        "first_name" => "sub1",
                        "last_name" => "sub1",
                        "phone_number" => "+33622484646",
                        "zip_code" => "adr1"
                    ]
                ]
            ]
        ];
    }
}
