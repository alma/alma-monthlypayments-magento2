<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;


class Collect extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Alma_MonthlyPayments::form/field/collect.phtml';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        Logger $logger,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->logger = $logger;
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * Generate collect button html
     *
     * @return string
     * @throws LocalizedException
     */
    public function getButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'collect_button',
                'label' => __('Collect Logs'),
            ]
        );

        return $button->toHtml();
    }

    /**
     * Return ajax url for collect button
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('alma_monthly/system_config/collect');
    }



}
