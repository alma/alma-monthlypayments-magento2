<?php

namespace Alma\MonthlyPayments\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;

class RefundDataBuilder implements BuilderInterface
{


    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject): array
    {
        /** @var PaymentDataObject $buildSubject['payment'] */
        $refundPayload['payment_id'] =  $this->getAlmaPaymentId($buildSubject['payment']);
        $refundPayload['merchant_id'] = $this->getAlmaMerchantId();
        $refundPayload['amount'] = $buildSubject['amount'];
        return $refundPayload;
    }

    /**
     * @return string
     */
    public function getAlmaMerchantId(): string
    {
        return $this->config->getMerchantId();
    }
    /**
     * @param InfoInterface $payment
     *
     * @return string
     */
    public function getAlmaPaymentId(InfoInterface $payment): string
    {
        return $payment->getAdditionalInformation(Config::ORDER_PAYMENT_ID);
    }
}
