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
     * @return string
     */
    public function getOrderPaymentMethodCode(OrderInterface $order): string
    {
        /** @var OrderPaymentInterface $payment */
        return $order->getPayment()->getMethod();
    }
}
