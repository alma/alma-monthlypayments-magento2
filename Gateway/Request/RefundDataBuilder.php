<?php

namespace Alma\MonthlyPayments\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;

class RefundDataBuilder implements BuilderInterface
{


    /**
     * @var Config
     */
    private $config;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Config $config
     */
    public function __construct(
        Logger $logger,
        Config $config,
        OrderRepository $orderRepository
    ) {
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject): array
    {
        /** @var PaymentDataObject $paymentDO */
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment   = $paymentDO->getPayment();
        $orderDO   = $paymentDO->getOrder();
        $order = $this->orderRepository->get($orderDO->getId());
        /** @var InfoInterface $payment */
        $refundPayload['payment_id'] =  $payment->getAdditionalInformation(Config::ORDER_PAYMENT_ID);
        $refundPayload['merchant_id'] = $this->config->getMerchantId();
        $refundPayload['amount'] = $buildSubject['amount'];
        $refundPayload['total_refund'] = $this->getTotalRefunded();
        $refundPayload['order_total'] = $order->getGrandTotal();
        return $refundPayload;
    }



}
