<?php

namespace Alma\MonthlyPayments\Api\Data;

interface CollectDataInterface
{
    /**
     * Collect configuration data Api return void value but an HTTP response is sent to the client
     * @return void
     */
    public function collect();
}
