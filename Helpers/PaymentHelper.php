<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Entities\Payment;
use InvalidArgumentException;
use Magento\Framework\App\Helper\AbstractHelper;

class PaymentHelper extends AbstractHelper
{
    const NO_ORDER_ID = 'No order_id in alma Payment';
    const PAYMENT_ORDER_ID_KEY = 'order_id';

    /**
     * @param Payment $almaPayment
     * @throws InvalidArgumentException
     * @return string
     */
    public function getOrderIdFromAlmaPayment(Payment $almaPayment): string
    {
        $order_id = null;
        if (isset($almaPayment->custom_data[self::PAYMENT_ORDER_ID_KEY])) {
            $order_id = $almaPayment->custom_data[self::PAYMENT_ORDER_ID_KEY];
        }

        if (!isset($order_id)) {
            throw new InvalidArgumentException(self::NO_ORDER_ID);
        }
        return $order_id;
    }

    /**
     * Generate plan key with an Alma payment
     *
     * @param Payment $almaPayment
     * @return string
     */
    public function getAlmaPaymentPlanKey(Payment $almaPayment): string
    {
        return sprintf('general:%s:%s:%s', $almaPayment->installments_count, $almaPayment->deferred_days, $almaPayment->deferred_months);
    }
}
