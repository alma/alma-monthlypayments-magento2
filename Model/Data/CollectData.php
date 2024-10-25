<?php

namespace Alma\MonthlyPayments\Model\Data;

use Alma\MonthlyPayments\Api\Data\CollectDataInterface;

class CollectData implements CollectDataInterface
{

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
       return "Collecting data";
    }
}
