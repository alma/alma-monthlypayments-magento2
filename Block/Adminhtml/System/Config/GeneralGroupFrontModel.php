<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\System\Config;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Magento\Config\Block\System\Config\Form\Fieldset as BlockFieldSet;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class GeneralGroupFrontModel extends BlockFieldSet
{
    private Config $config;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        Config $config,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct(
            $context,
            $authSession,
            $jsHelper,
            $data,
            $secureRenderer
        );
        $this->config = $config;
    }

    protected function _getHeaderCommentHtml($element)
    {
        if (!$this->config->getIsActive()) {
            $translatedComment  = __('Attention: You have to enable the plugin for accepting payments. Meanwhile, you can complete your configuration.');
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
