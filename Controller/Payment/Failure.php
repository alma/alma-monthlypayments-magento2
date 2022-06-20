<?php

namespace Alma\MonthlyPayments\Controller\Payment;



use Alma\MonthlyPayments\Helpers\CancelOrderAction;

class Failure extends CancelOrderAction
{
    CONST CANCEL_MESSAGE = 'Rejected payment';
}
