<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Api\Insurance;

use Alma\API\Client;
use Alma\API\Endpoints\Insurance;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Api\Insurance\InsuranceUpdate;
use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription;
use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription\CollectionFactory;
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

    private $subscriptionRessourceModel;
    private $subscriptionCollection;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->request = $this->createMock(\Magento\Framework\Webapi\Rest\Request::class);
        $this->logger = $this->createMock(\Alma\MonthlyPayments\Helpers\Logger::class);
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->subscriptionCollection = $this->createMock(CollectionFactory::class);
        $this->subscriptionRessourceModel = $this->createMock(Subscription::class);
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
            $this->subscriptionCollection,
            $this->subscriptionRessourceModel
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
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn(['sid' => 'invalid_subscription_key']);

        $this->expectException(\Magento\Framework\Webapi\Exception::class);
        $instance = $this->createInstance();
        $instance->update();
    }

    public function testGivenSubscriptionIdReturnApiErrorMustReturnError(): void
    {
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn(['subscription_id' => 'valid_subscription_key']);

        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint->expects($this->once())->method('getSubscription')->willThrowException(new \Alma\API\Exceptions\AlmaException('error'));
        $almaClient = $this->createMock(Client::class);
        $almaClient->insurance = $insuranceEndpoint;
        $this->almaClient->method('getDefaultClient')->willReturn($almaClient);
        $this->expectException(\Magento\Framework\Webapi\Exception::class);
        $instance = $this->createInstance();
        $instance->update();
    }
    public function testGivenSubscriptionIdReturnEmptySubscriptionListMustReturnError(): void
    {
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn(['subscription_id' => 'valid_subscription_key']);

        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint->expects($this->once())->method('getSubscription')->willReturn(
            ['subscriptions' => []]
        );
        $almaClient = $this->createMock(Client::class);
        $almaClient->insurance = $insuranceEndpoint;
        $this->almaClient->method('getDefaultClient')->willReturn($almaClient);
        $this->expectException(\Magento\Framework\Webapi\Exception::class);
        $instance = $this->createInstance();
        $instance->update();
    }

    public function testGivenEmptyDbSubscriptionMustThrow():void
    {
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn(['subscription_id' => 'valid_subscription_key']);

        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint->expects($this->once())->method('getSubscription')->with(['id' => 'valid_subscription_key'])->willReturn($this->getSubscriptionResultData());
        $almaClient = $this->createMock(Client::class);
        $almaClient->insurance = $insuranceEndpoint;
        $this->almaClient->method('getDefaultClient')->willReturn($almaClient);
        $dbSubscriptionMock = $this->createMock(\Alma\MonthlyPayments\Model\Insurance\Subscription::class);
        $dbSubscriptionMock->method('getId')->willReturn(null);
        $collectionMock = $this->createMock(Subscription\Collection::class);
        $collectionMock->method('getFirstItem')->willReturn($dbSubscriptionMock);
        $collectionMock->method('addFieldToFilter')->willReturn($collectionMock);

        $this->subscriptionCollection->method('create')->willReturn($collectionMock);
        $this->expectException(\Exception::class);
        $instance = $this->createInstance();
        $instance->update();
    }

    public function testGivenAValidApiReturnMustLoadAndSaveModelFromRepository(): void
    {
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn(['subscription_id' => 'valid_subscription_key']);

        $insuranceEndpoint = $this->createMock(Insurance::class);
        $apiResult = $this->getSubscriptionResultData();
        $insuranceEndpoint->expects($this->once())->method('getSubscription')->with(['id' => 'valid_subscription_key'])->willReturn($this->getSubscriptionResultData($apiResult));
        $almaClient = $this->createMock(Client::class);
        $almaClient->insurance = $insuranceEndpoint;
        $this->almaClient->method('getDefaultClient')->willReturn($almaClient);
        $dbSubscriptionMock = $this->createMock(\Alma\MonthlyPayments\Model\Insurance\Subscription::class);
        $dbSubscriptionMock->method('getId')->willReturn(1);
        $dbSubscriptionMock->method('setSubscriptionState')->with($apiResult['subscriptions'][0]['state']);
        $dbSubscriptionMock->method('setSubscriptionBrokerId')->with($apiResult['subscriptions'][0]['broker_subscription_id']);
        $this->subscriptionRessourceModel->expects($this->once())->method('save')->with($dbSubscriptionMock);
        $collectionMock = $this->createMock(Subscription\Collection::class);
        $collectionMock->method('getFirstItem')->willReturn($dbSubscriptionMock);
        $collectionMock->method('addFieldToFilter')->willReturn($collectionMock);

        $this->subscriptionCollection->method('create')->willReturn($collectionMock);

        $instance = $this->createInstance();
        $this->assertNull($instance->update());
    }


    private function getSubscriptionResultData(): array
    {
        return [
            "subscriptions" => [
                [
                    "amount" => 1312,
                    "cms_reference" => "19",
                    "contract_id" => "insurance_contract_755vkKQUnezKPvzMWc4Qeq",
                    "id" => "subscription_5FM3wla3WvVjFaOUb06nXt",
                    "broker_subscription_id" => "xxxx",
                    "state" => "cancel",
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
