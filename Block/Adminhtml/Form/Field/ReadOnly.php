<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\Form\Field;

use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ReadOnly extends Field
{
    /**
     * @var PaymentPlansHelper
     */
    private $paymentPlansHelper;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param PaymentPlansHelper $paymentPlansHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Logger $logger ,
        PaymentPlansHelper $paymentPlansHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->logger = $logger;
        $this->paymentPlansHelper = $paymentPlansHelper;
    }
    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setReadonly(true);
        if (!$this->paymentPlansHelper->paymentTriggerIsAllowed()){
            $element->setComment(__("If you are interested in this feature, please get closer to your Alma contact or by sending an email to support@getalma.eu"));
        }
        return $element->getElementHtml();
    }
}
