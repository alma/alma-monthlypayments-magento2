<?php

namespace Alma\MonthlyPayments\Controller\Payment;

use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\OrderHelper;
use Alma\MonthlyPayments\Helpers\PaymentHelper;
use InvalidArgumentException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Alma\MonthlyPayments\Helpers\PaymentValidation;
use Magento\Sales\Api\OrderManagementInterface;

class Failure extends Action
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var PaymentValidation
     */
    private $paymentValidation;
    /**
     * @var \Alma\API\Entities\Payment
     */
    private $almaPayment;
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;
    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param PaymentValidation $paymentValidation
     * @param OrderManagementInterface $orderManagement
     * @param PaymentHelper $paymentHelper
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        Context $context,
        Logger $logger,
        PaymentValidation $paymentValidation,
        OrderManagementInterface $orderManagement,
        PaymentHelper $paymentHelper,
        OrderHelper $orderHelper
    )
    {
        parent::__construct($context);

        $this->logger = $logger;
        $this->paymentValidation = $paymentValidation;
        $this->orderManagement = $orderManagement;
        $this->paymentHelper = $paymentHelper;
        $this->orderHelper = $orderHelper;
    }

    public function execute()
    {
        $paymentId = $this->getRequest()->getParam('pid');
        try {
            $this->almaPayment = $this->paymentValidation->getAlmaPayment($paymentId);
        } catch (RequestError $e) {
            $this->logger->error('Cancel order - get alma Payment error',[$e->getMessage()]);
            return $this->redirectToCart();
        }

        try {
            $order = $this->orderHelper->getOrderById($this->paymentHelper->getOrderIdFromAlmaPayment($this->almaPayment));
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Cancel order - get order error',[$e->getMessage()]);
            return $this->redirectToCart();
        }
        try {
            $order->addStatusHistoryComment(__('Rejected payment'))->save();
        } catch (\Exception $e){
            $this->logger->error('Cancel order - save history error',[$e->getMessage()]);
        }
        $this->orderManagement->cancel($order->getEntityId());
        return $this->redirectToCart();
    }

    private function redirectToCart()
    {
        return $this->_redirect('checkout/cart');
    }

}
