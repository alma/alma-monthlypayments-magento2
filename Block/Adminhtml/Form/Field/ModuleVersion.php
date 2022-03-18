<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\Form\Field;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ResourceInterface;


class ModuleVersion extends Field
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var array
     */
    private $data;
    /**
     * @var ResourceInterface
     */
    private $resource;

    /**
     * @param Context $context
     * @param ResourceInterface $resource
     * @param array $data
     */
    public function __construct(
        Context $context,
        ResourceInterface $resource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->resource = $resource;
    }
    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element):string
    {
        $moduleVersion= $this->resource->getDataVersion('Alma_MonthlyPayments');
        $element->setComment(__("v". $moduleVersion));
        return $element->getElementHtml();
    }
}