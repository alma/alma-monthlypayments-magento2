<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\Insurance;

use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;

class ReturnActionButton extends Container
{
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        $this->buttonList->add(
            'back',
            [
                'label' => __('Back'),
                'onclick' => "window.location.href = '" . $this->getUrl('alma_monthly/insurance/subscriptions') . "'",
                'class' => 'back'
            ]
        );
        return parent::_prepareLayout();
    }
}
