<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Entities\Payment;
use Magento\Framework\App\Helper\AbstractHelper;
Use InvalidArgumentException;

class PaymentHelper extends AbstractHelper
{
    CONST NO_ORDER_ID = 'No order_id in alma Payment';
    CONST PAYMENT_ORDER_ID_KEY = 'order_id';

    /**
     * @param Payment $almaPayment
     * @return string
     */
    public function getOrderIdFromAlmaPayment(Payment $almaPayment):string
    {
        $order_id = null;
        if(isset($almaPayment->custom_data[self::PAYMENT_ORDER_ID_KEY])){
            $order_id = $almaPayment->custom_data[self::PAYMENT_ORDER_ID_KEY];
        }

        if(!isset($order_id)){
            throw new InvalidArgumentException(self::NO_ORDER_ID);
        }
        return $order_id;
    }
}
