<?php

namespace Alma\MonthlyPayments\Ui\Component\Insurance\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;

class SubscriptionModeOptions implements OptionSourceInterface
{

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        {
            $options = [];
            $options[] = ['label' => 'Test', 'value' => 'test'];
            $options[] = ['label' => 'Live', 'value' => 'live'];
            return $options;
        }
    }
}
