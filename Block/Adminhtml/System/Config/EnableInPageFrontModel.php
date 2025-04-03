<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class EnableInPageFrontModel extends Field
{
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
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
        $element->setComment(__("Let your customers pay with Alma in a secure pop-up, without leaving your site. <a href='https://docs.almapay.com/docs/in-page-adobe-commerce' title='Learn more' target='_blank'>Learn more</a>"));
        return $element->getElementHtml();
    }
}
