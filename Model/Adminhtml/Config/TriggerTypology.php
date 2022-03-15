<?php

namespace Alma\MonthlyPayments\Model\Adminhtml\Config;

use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\Data\OptionSourceInterface;

class TriggerTypology implements OptionSourceInterface
{
    const TRIGGER_LIST = [
        ['value' => 'At shipping', 'label' => 'At shipping']
    ];
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Logger $logger
    )
    {
        $this->logger = $logger;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $optionArray = [];
        foreach (self::TRIGGER_LIST as $trigger) {
            $optionArray[]=['value' => $trigger['value'], 'label' => __($trigger['label'])];
        }
        return $optionArray;
    }

}
