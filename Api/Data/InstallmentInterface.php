<?php


namespace Alma\MonthlyPayments\Api\Data;


interface InstallmentInterface
{
    /**
     * Returns a UNIX timestamp indicating the date this installment is due
     *
     * @return int
     * @api
     */
    public function getDueDate();

    /**
     *  Returns the amount of fees to be paid for by the customer
     *
     * @return int
     * @api
     */
    public function getCustomerFee();

    /**
     *  Returns the principal amount to be paid for this installment
     *
     * @return int
     * @api
     */
    public function getPurchaseAmount();
}
