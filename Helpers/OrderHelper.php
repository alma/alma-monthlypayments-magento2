<?php

namespace Alma\MonthlyPayments\Helpers;

use Magento\Sales\Api\Data\OrderPaymentInterface;

class OrderHelper
{
    public function getOrderCurrency($order):string
    {
        return $order->getOrderCurrencyCode();
    }

    public function getOrderPaymentAmount($order):int
    {
        /** @var OrderPaymentInterface $payment */
        $payment = $order->getPayment();
        return Functions::priceToCents($payment->getAmountPaid() - $payment->getAmountRefunded());
    }
    public function getOrderPaymentMethodCode($order):string
    {
        /** @var OrderPaymentInterface $payment */
        return $order->getPayment()->getMethod();
    }
}
