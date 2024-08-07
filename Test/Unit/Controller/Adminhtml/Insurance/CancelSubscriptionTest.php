<?php

namespace Alma\MonthlyPayments\Test\Unit\Controller\Adminhtml\Insurance;

use Alma\API\Client;
use Alma\API\Endpoints\Insurance;
use Alma\API\Exceptions\InsuranceCancelPendingException;
use Alma\API\Exceptions\RequestException;
use Alma\MonthlyPayments\Controller\Adminhtml\Insurance\CancelSubscription;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\InsuranceSubscriptionHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceSubscriptionException;
use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\Validator\Exception;
use PHPUnit\Framework\TestCase;

class CancelSubscriptionTest extends TestCase
{

    /**
     * @var Context
     */
    private $context;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Json
     */
    private $jsonResponse;
    /**
     * @var AlmaClient
     */
    private $almaClient;
    /**
     * @var Insurance
     */
    private $insuranceMock;
    /**
     * @var Subscription
     */
    private $subscriptionResourceModel;
    private $dbSubscriptionMock;
    private $insuranceSubscriptionHelper;

    const CANCELLATION_REASON = 'Cancellation reason';
    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->request = $this->createMock(Request::class);
        $this->context->method('getRequest')->willReturn($this->request);
        $this->jsonResponse = $this->createMock(Json::class);
        $this->jsonResponse->method('setData')->willReturn($this->jsonResponse);
        $this->jsonFactory = $this->createMock(JsonFactory::class);
        $this->jsonFactory->method('create')->willReturn($this->jsonResponse);
        $this->logger = $this->createMock(Logger::class);
        $this->insuranceMock = $this->createMock(Insurance::class);
        $client = $this->createMock(Client::class);
        $client->insurance = $this->insuranceMock;
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->almaClient->method('getDefaultClient')->willReturn($client);
        $this->dbSubscriptionMock = $this->createMock(\Alma\MonthlyPayments\Model\Insurance\Subscription::class);
        $this->subscriptionResourceModel = $this->createMock(Subscription::class);
        $this->insuranceSubscriptionHelper = $this->createMock(InsuranceSubscriptionHelper::class);
        $this->insuranceSubscriptionHelper->method('getDbSubscription')->willReturn($this->dbSubscriptionMock);
    }

    public function tearDown():void
    {
        $this->dbSubscriptionMock = null;
    }

    protected function newCancelSubscription(): CancelSubscription
    {
        return new CancelSubscription(
            $this->almaClient,
            $this->insuranceSubscriptionHelper,
            $this->subscriptionResourceModel,
            $this->logger,
            $this->jsonFactory,
            $this->context
        );
    }

    //Given an empty post request
    public function testShouldReturnAnErrorMessageWhenRequestIsEmpty()
    {
        $this->request->method('getPostValue')->willReturn([]);
        $this->jsonResponse->expects($this->once())->method('setData')->with(['state' => 'failed', 'message' => CancelSubscription::NO_SUBSCRIPTION_ID_MESSAGE]);
        $this->newCancelSubscription()->execute();
    }

    //Given a request with post data when subscriptionId is missing
    public function testShouldReturnAnErrorMessageWhenSubscriptionIdIsMissing()
    {
        $this->request->method('getPostValue')->willReturn(['subscriptionBrokerId' => '123']);
        $this->jsonResponse->expects($this->once())->method('setData')->with(['state' => 'failed', 'message' => CancelSubscription::NO_SUBSCRIPTION_ID_MESSAGE]);
        $this->newCancelSubscription()->execute();
    }

    // Given a request with post subscriptionId data but no cancellation_reaseon when subscriptionId is present and Api Error
    public function testShouldCallApiClientCancelEndpointWithSubscriptionIdAndReturnAnErrorNoCallSetCancellationReason()
    {
        $subscriptionId = 'subscription_id1234';
        $this->request->method('getPostValue')->willReturn(['subscriptionId' => $subscriptionId]);

        $this->insuranceMock
            ->expects($this->once())
            ->method('cancelSubscription')
            ->with($subscriptionId)
            ->willThrowException(new RequestException('Error cancelling subscription'));
        $this->dbSubscriptionMock->expects($this->once())->method('setSubscriptionState')->with('failed');
        $this->dbSubscriptionMock->expects($this->once())->method('setCancellationReason')->with('');
        $this->subscriptionResourceModel->expects($this->once())->method('save')->with($this->dbSubscriptionMock);
        $this->jsonResponse->expects($this->once())->method('setData')->with(['state' => 'failed', 'message' => CancelSubscription::CANCEL_ERROR_MESSAGE]);

        $this->newCancelSubscription()->execute();
    }

    //Given a request with post data when subscriptionId is present and Api success Load throw an exception
    public function testShouldCallApiClientCancelEndpointWithSubscriptionIdAndReturnSuccessWithErrorOnLoad()
    {
        $subscriptionId = 'subscription_id123456';
        $this->request->method('getPostValue')->willReturn(['subscriptionId' => $subscriptionId]);

        $this->insuranceMock
            ->expects($this->never())
            ->method('cancelSubscription');

        $this->insuranceSubscriptionHelper->expects($this->once())->method('getDbSubscription')->with($subscriptionId)->willThrowException(new AlmaInsuranceSubscriptionException(__('Impossible to load subscription data')));
        $this->jsonResponse->expects($this->once())->method('setData')->with(['state' => 'failed', 'message' => 'Impossible to load subscription data']);
        $this->dbSubscriptionMock->expects($this->never())->method('setSubscriptionState');
        $this->dbSubscriptionMock->expects($this->never())->method('setCancellationReason');
        $this->newCancelSubscription()->execute();
    }
    public function testShouldCallApiClientCancelEndpointWithSubscriptionIdAndReturnSuccessWithErrorOnSave()
    {
        $subscriptionId = 'subscription_id123456';
        $this->request->method('getPostValue')->willReturn(['subscriptionId' => $subscriptionId, 'cancelReason' => self::CANCELLATION_REASON]);

        $this->insuranceMock
            ->expects($this->once())
            ->method('cancelSubscription')
            ->with($subscriptionId);
        $this->dbSubscriptionMock->expects($this->once())->method('setSubscriptionState')->with('canceled');
        $this->dbSubscriptionMock->expects($this->once())->method('setCancellationReason')->with(self::CANCELLATION_REASON);
        $this->subscriptionResourceModel->expects($this->once())->method('save')->with($this->dbSubscriptionMock)->willThrowException(new \Exception('Impossible to save subscription data'));
        $this->jsonResponse->expects($this->once())->method('setData')->with(['state' => 'canceled', 'message' => CancelSubscription::CANCEL_SUCCESS_MESSAGE]);
        $this->newCancelSubscription()->execute();
    }
    // Given a request with post data when subscriptionId is present and Api success
    public function testShouldCallApiClientCancelEndpointWithSubscriptionIdAndReturnSuccess()
    {
        $subscriptionId = 'subscription_id123456';
        $this->request->method('getPostValue')->willReturn(['subscriptionId' => $subscriptionId, 'cancelReason' => self::CANCELLATION_REASON]);

        $this->insuranceMock
            ->expects($this->once())
            ->method('cancelSubscription')
            ->with($subscriptionId);
        $this->dbSubscriptionMock->expects($this->once())->method('setSubscriptionState')->with('canceled');
        $this->dbSubscriptionMock->expects($this->once())->method('setCancellationReason')->with(self::CANCELLATION_REASON);
        $this->subscriptionResourceModel->expects($this->once())->method('save')->with($this->dbSubscriptionMock);
        $this->jsonResponse->expects($this->once())->method('setData')->with(['state' => 'canceled', 'message' => CancelSubscription::CANCEL_SUCCESS_MESSAGE]);
        $this->newCancelSubscription()->execute();
    }
    // Given a request with post data when subscriptionId is present and Api return 410 and throw InsuranceCancelPendingException out of delay
    public function testShouldCallApiClientCancelEndpointWithSubscriptionIdAndReturnStatePendingCancellation()
    {
        $subscriptionId = 'subscription_id1234';
        $this->request->method('getPostValue')->willReturn(['subscriptionId' => $subscriptionId,  'cancelReason' => self::CANCELLATION_REASON]);

        $this->insuranceMock
            ->expects($this->once())
            ->method('cancelSubscription')
            ->with($subscriptionId)
            ->willThrowException(new InsuranceCancelPendingException('Out of delay'));

        $this->dbSubscriptionMock->expects($this->once())->method('setSubscriptionState')->with('pending_cancellation');
        $this->dbSubscriptionMock->expects($this->once())->method('setCancellationReason')->with(self::CANCELLATION_REASON);
        $this->subscriptionResourceModel->expects($this->once())->method('save')->with($this->dbSubscriptionMock);
        $this->jsonResponse->expects($this->once())->method('setData')->with(['state' => 'pending_cancellation', 'message' => CancelSubscription::CANCEL_PENDING_MESSAGE]);

        $this->newCancelSubscription()->execute();
    }

}
