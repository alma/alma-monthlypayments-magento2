<?php

namespace Alma\MonthlyPayments\Api\Insurance;

use Alma\MonthlyPayments\Api\Data\Insurance\InsuranceUpdateResultInterface;

interface InsuranceUpdateInterface
{

    /**
     * Update insurance subscription
     * @return void
     * @api
     */
    public function update();
}
