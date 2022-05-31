<?php

namespace Alma\MonthlyPayments\Helpers;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class OrderHelper
{
    /**
     * @param OrderInterface $order
     *
     * @return string
     */
    public function getOrderCurrency(OrderInterface $order): string
    {
        return $order->getOrderCurrencyCode();
    }

    /**
     * @param OrderInterface $order
     *
     * @return int
     */
    public function getOrderPaymentAmount(OrderInterface $order): int
    {
        /** @var OrderPaymentInterface $payment */
        $payment = $order->getPayment();
        return Functions::priceToCents($payment->getAmountPaid() - $payment->getAmountRefunded());
    }

    /**
     * @param OrderInterface $order
     *
     * @return string
     */
    public function getOrderPaymentMethodCode(OrderInterface $order): string
    {
        /** @var OrderPaymentInterface $payment */
        return $order->getPayment()->getMethod();
    }
}
