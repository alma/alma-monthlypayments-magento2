<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\System\Config;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Magento\Config\Block\System\Config\Form\Fieldset as BlockFieldSet;

class GeneralGroupFrontModel extends BlockFieldSet
{
    private $config;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        Config $config,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $authSession,
            $jsHelper,
            $data
        );
        $this->config = $config;
    }

    protected function _getHeaderCommentHtml($element)
    {
        if (!$this->config->getIsActive()) {
            $translatedComment  = __('Warning: You have to enable the plugin for accepting payments. Meanwhile, you can complete your configuration.');
            $element->setComment(
                sprintf(
                    '<div style="color: red">%s</div>',
                    $translatedComment
                )
            );
        }
        return parent::_getHeaderCommentHtml($element);
    }
}
