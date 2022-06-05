<?php

namespace Alma\MonthlyPayments\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\Logger;
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
        $payment = $buildSubject['payment']->getPayment();
        /** @var InfoInterface $payment */
        $refundPayload['payment_id'] =  $payment->getAdditionalInformation(Config::ORDER_PAYMENT_ID);
        $refundPayload['merchant_id'] = $this->config->getMerchantId();
        $refundPayload['amount'] = $buildSubject['amount'];
        return $refundPayload;
    }

}
