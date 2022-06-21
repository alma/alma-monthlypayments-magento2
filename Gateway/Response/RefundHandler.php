<?php

namespace Alma\MonthlyPayments\Gateway\Response;

use Alma\API\Entities\Payment as AlmaPayment;
use Alma\API\Entities\Refund;
use Alma\MonthlyPayments\Helpers\Functions;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;

class RefundHandler implements HandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $amountDO = SubjectReader::readAmount($handlingSubject);
        /** @var  Payment $payment */
        $payment = $paymentDO->getPayment();

        /** @var AlmaPayment $almaPayment */
        $almaPayment = $response['almaRefund'];
        $lastRefundId = '';
        $lastRefundData = [];
        foreach ($almaPayment->refunds as $refund) {
            /** @var Refund $refund */
            $lastRefundId = $refund->id;
            $lastRefundData = [
                'created' => $refund->created,
                'amount' => $payment->formatPrice(Functions::priceFromCents($refund->amount)),
            ];
        }
        if ($response['isFullRefund']) {
            $lastRefundData['customer_fee'] = $payment->formatPrice(Functions::priceFromCents($almaPayment->customer_fee));
            $lastRefundData['magento_refund'] = $payment->formatPrice($amountDO);
        }
        $payment->setTransactionId($lastRefundId);
        $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $lastRefundData);
        $payment->addTransaction(TransactionInterface::TYPE_REFUND);
    }
}
