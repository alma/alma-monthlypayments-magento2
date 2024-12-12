<?php

namespace Alma\MonthlyPayments\Api\Data;

use Magento\Framework\App\ResponseInterface;

interface CollectDataInterface
{
    /**
     * Collect configuration data Api return void value but an HTTP response is sent to the client
     * @return ResponseInterface
     */
    public function collect();
}
