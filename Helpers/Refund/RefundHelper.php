<?php

namespace Alma\MonthlyPayments\Helpers\Refund;

use Alma\API\Entities\Payment;
use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class RefundHelper extends AbstractHelper
{
    /**
     * @var AlmaClient
     */
    private $almaClient;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Context $context,
        Logger $logger,
        AlmaClient $almaClient
    ) {
        parent::__construct($context);
        $this->almaClient = $almaClient->getDefaultClient();
        $this->logger = $logger;
    }

    /**
     * @param $paymentId
     * @param $price
     *
     * @return Payment
     * @throws RequestError
     */
    public function makePartialRefund($paymentId, $price): Payment
    {
        try {
            $payment = $this->almaClient->payments->partialRefund($paymentId, $price);
        } catch (RequestError $e) {
            $this->logger->error('Make partial refund exception', [$e->getMessage()]);
            throw new RequestError($e->getMessage());
        }
        return $payment;
    }

}
