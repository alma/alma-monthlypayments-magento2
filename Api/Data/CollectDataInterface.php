<?php

namespace Alma\MonthlyPayments\Api\Data;

use Magento\Framework\App\Response\HttpInterface;

interface CollectDataInterface
{
    /**
     * Collect configuration data
     * @return HttpInterface
     */
    public function collect();
}
