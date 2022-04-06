<?php

namespace Alma\MonthlyPayments\Controller\Payment;

use Alma\API\Entities\Order;
use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\Logger;
use InvalidArgumentException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Alma\MonthlyPayments\Helpers\PaymentValidation;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\OrderFactory;

class CancelOrder extends Action
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
     * @var OrderFactory
     */
    private $orderFactory;
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    public function __construct(
        Context $context,
        Logger $logger,
        PaymentValidation $paymentValidation,
        OrderFactory $orderFactory,
        OrderManagementInterface $orderManagement
    )
    {
        parent::__construct($context);

        $this->logger = $logger;
        $this->paymentValidation = $paymentValidation;
        $this->orderFactory = $orderFactory;
        $this->orderManagement = $orderManagement;
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
            $order = $this->getOrder();
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Cancel order - get order error',[$e->getMessage()]);
            return $this->redirectToCart();
        }
        try {
            $order->addStatusHistoryComment(__('Order canceled by customer'))->save();
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

    private function getOrder():OrderInterface
    {
        $orderId = $this->getOrderId();
        $orderModel = $this->orderFactory->create();
        return $orderModel->loadByIncrementId($orderId);
    }

    private function getOrderId():string
    {
        $order_id = null;
        if(isset($this->almaPayment->custom_data['order_id'])){
            $order_id = $this->almaPayment->custom_data['order_id'];
        }

        if(!isset($order_id)){
            throw new InvalidArgumentException('No order_id in alma Payment');
        }
        return $order_id;
    }

}
