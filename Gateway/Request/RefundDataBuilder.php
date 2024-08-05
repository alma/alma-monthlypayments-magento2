<?php

namespace Alma\MonthlyPayments\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\OrderRepository;

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
     * @param Config $config
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        Config $config,
        OrderRepository $orderRepository
    ) {
        $this->config = $config;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param array $buildSubject
     *
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function build(array $buildSubject): array
    {
        /** @var PaymentDataObject $paymentDO */
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment   = $paymentDO->getPayment();
        $orderDO   = $paymentDO->getOrder();
        $order = $this->orderRepository->get($orderDO->getId());
        $refundPayload['payment_id'] =  $payment->getAdditionalInformation(Config::ORDER_PAYMENT_ID);
        $refundPayload['merchant_id'] = $this->config->getMerchantId($order->getStoreId());
        $refundPayload['amount'] = $buildSubject['amount'];
        $refundPayload['total_refund'] = $order->getTotalRefunded();
        $refundPayload['order_total'] = $order->getGrandTotal();
        $refundPayload['store_id'] = $order->getStoreId();
        return $refundPayload;
    }
}
