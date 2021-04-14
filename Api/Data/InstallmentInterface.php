<?php


namespace Alma\MonthlyPayments\Api\Data;


interface InstallmentInterface
{
    /**
     * Returns a UNIX timestamp indicating the date this installment is due
     *
     *  @api
     *  @return int
     */
    public function getDueDate();

    /**
     *  Returns the amount of fees to be paid for by the customer
     *
     *  @api
     *  @return int
     */
    public function getCustomerFee();

    /**
     *  Returns the principal amount to be paid for this installment
     *
     *  @api
     *  @return int
     */
    public function getPurchaseAmount();
}
