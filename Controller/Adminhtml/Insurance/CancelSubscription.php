<?php

namespace Alma\MonthlyPayments\Controller\Adminhtml\Insurance;

use Alma\API\Entities\Insurance\Subscription;
use Alma\API\Exceptions\AlmaException;
use Alma\API\Exceptions\InsuranceCancelPendingException;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\InsuranceSubscriptionHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Validator\Exception;


class CancelSubscription extends Action
{
    const NO_SUBSCRIPTION_ID_MESSAGE = 'No subscription Id in post data';
    const CANCEL_ERROR_MESSAGE = 'Impossible to cancel subscription';
    const CANCEL_SUCCESS_MESSAGE = 'Subscription cancelled';
    const CANCEL_PENDING_MESSAGE = 'Out of delay to cancel subscription';

    private $resultJsonFactory;
    private $logger;
    private $almaClient;
    private $subscriptionResourceModel;
    private $insuranceSubscriptionHelper;


    /**
     * @param Logger $logger
     * @param JsonFactory $resultJsonFactory
     * @param Context $context
     */
    public function __construct(
        AlmaClient  $almaClient,
        InsuranceSubscriptionHelper $insuranceSubscriptionHelper,
        \Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription $subscriptionResourceModel,
        Logger      $logger,
        JsonFactory $resultJsonFactory,
        Context     $context
    )
    {
        parent::__construct(
            $context
        );
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        $this->almaClient = $almaClient;
        $this->subscriptionResourceModel = $subscriptionResourceModel;
        $this->insuranceSubscriptionHelper = $insuranceSubscriptionHelper;
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();
        $response = ['state' => Subscription::STATE_CANCELLED, 'message' => self::CANCEL_SUCCESS_MESSAGE];

        $post = $this->getRequest()->getPostValue();
        if (empty($post) || !is_array($post) || !array_key_exists('subscriptionId', $post)) {
            $response = ['state' => Subscription::STATE_FAILED, 'message' => self::NO_SUBSCRIPTION_ID_MESSAGE];
            return $result->setData($response);
        }

        try {
            $dbSubscription = $this->insuranceSubscriptionHelper->getDbSubscription($post['subscriptionId']);
        } catch (Exception $e) {
            $this->logger->error('Impossible to load subscription data', [$e->getMessage()]);
            $response = ['state' => Subscription::STATE_FAILED, 'message' => 'Impossible to load subscription data'];
            return $result->setData($response);
        }

        try {
            $dbSubscription->setCancellationRequestDate(new \DateTime());
            $this->almaClient->getDefaultClient()->insurance->cancelSubscription($post['subscriptionId']);
            $dbSubscription->setCancellationDate(new \DateTime());
        } catch (InsuranceCancelPendingException $e) {
            $response = ['state' => Subscription::STATE_PENDING_CANCELLATION, 'message' => self::CANCEL_PENDING_MESSAGE];
        } catch (AlmaException $e) {
            $this->logger->error('Error cancelling subscription', [$e->getMessage()]);
            $response = ['state' => Subscription::STATE_FAILED, 'message' => self::CANCEL_ERROR_MESSAGE];
        }

        try {
            $dbSubscription->setSubscriptionState($response['state']);
            $dbSubscription->setCancellationReason($post['cancelReason'] ?? '');
            $this->subscriptionResourceModel->save($dbSubscription);
        } catch (\Exception $e) {
            $this->logger->error('Impossible to load/save Subscription', [$post['subscriptionId']]);
        }

        return $result->setData($response);
    }

}
