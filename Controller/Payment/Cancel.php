<?php

namespace Alma\MonthlyPayments\Controller\Payment;

use Alma\MonthlyPayments\Helpers\CancelOrderAction;

class Cancel extends CancelOrderAction
{
    const CANCEL_MESSAGE = "Order canceled by customer";
}
