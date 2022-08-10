<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\System\Config\Fieldset;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;

class DynamicRowText extends AbstractBlock
{

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * @return string
     */
    public function toHtml(): string
    {
        $html = '<div style="' . $this->getColumn()['style'] . '">';
        $html .= '<%- ' . $this->getColumnName() . ' %>';
        $html .= '</div>';
        return  $html;
    }
}
