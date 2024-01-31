<?php

namespace Alma\MonthlyPayments\Ui\Component\Insurance\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;

class SubscriptionStateOptions implements OptionSourceInterface
{

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        {
            $options = [];
            $options[] = ['label' => 'Active', 'value' => 'started'];
            $options[] = ['label' => 'Failed', 'value' => 'failed'];
            $options[] = ['label' => 'Canceled', 'value' => 'canceled'];
            return $options;
        }
    }
}
