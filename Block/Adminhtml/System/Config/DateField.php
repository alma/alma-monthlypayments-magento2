<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\System\Config;

use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Stdlib\DateTime;

class DateField extends Field
{
    /**
     * @var Logger
     */
    private $logger;


    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Logger $logger,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->logger = $logger;
    }

    public function render(AbstractElement $element)
    {
        $element->setDateFormat(DateTime::DATE_INTERNAL_FORMAT);
        $element->setTimeFormat(null);
        return parent::render($element);
    }
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->lock();
        $element->setReadonly(true);
        return $element->getElementHtml();
    }
}
