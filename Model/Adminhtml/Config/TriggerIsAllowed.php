<?php

namespace Alma\MonthlyPayments\Model\Adminhtml\Config;

use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Data\Form\Element\AbstractElement;

class TriggerIsAllowed extends Yesno
{
    const ALLOWED_LABEL = 'Yes';
    const NOT_ALLOWED_LABEL = 'No';

    /**
     * @var PaymentPlansHelper
     */
    private $paymentPlansHelper;

    public function __construct(
        PaymentPlansHelper $paymentPlansHelper
    )
    {
        $this->paymentPlansHelper = $paymentPlansHelper;
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setDisabled('disabled');
        return $element->getElementHtml();
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $isAllowedValue = 0;
        $isAllowedLabel = self::NOT_ALLOWED_LABEL;
        if($this->paymentPlansHelper->paymentTriggerIsAllowed()){
            $isAllowedValue = 1;
            $isAllowedLabel = self::ALLOWED_LABEL;
        }
        return [['value' => $isAllowedValue, 'label' => __($isAllowedLabel)]];
    }
}
