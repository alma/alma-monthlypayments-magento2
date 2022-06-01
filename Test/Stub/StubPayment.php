<?php

namespace Alma\MonthlyPayments\Test\Stub;

/**
 * Class StubPayment
 *
 * @package Alma\MonthlyPayments\Test\Unit\Helpers\ShareOfCheckout
 */
class StubPayment
{
    private $amountPaid;
    private $amountRefunded;
    private $method;

    /**
     * @param $amountPaid
     * @param $amountRefunded
     * @param $methodCode
     */
    public function __construct($amountPaid, $amountRefunded, $methodCode)
    {
        $this->amountPaid = $amountPaid;
        $this->amountRefunded = $amountRefunded;
        $this->method = $methodCode;
    }

    /**
     * @return mixed
     */
    public function getAmountPaid()
    {
        return $this->amountPaid;
    }

    /**
     * @return mixed
     */
    public function getAmountRefunded()
    {
        return $this->amountRefunded;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }
}
