<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;


class InsuranceWidget extends Field
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
    protected function _getElementHtml(AbstractElement $element):string
    {

        $iframe = "<div id='alma-insurance-modal'></div>
                   <iframe id='config-alma-iframe'
                    class='alma-insurance-iframe'
                    width='100%'
                    height='100%'
                    src='https://protect.staging.almapay.com/almaBackOfficeConfiguration.html'>
                   </iframe>
                   <button id='almaSave' onclick='getBackOfficeWidgetData()'>Click me</button>
                   <script type='module' src='https://protect.staging.almapay.com/openInPageModal.js'></script>
                   <script>
                   function getBackOfficeWidgetData() {
                       console.log('in my get Data');
                           var widgetData = getAlmaWidgetData();
                       console.log(getAlmaWidgetData());
                   }
                   </script>
                   <input name='groups[alma_insurance][fields][alma_insurance_config][value]' type='hidden' value='Toto1234' />
                   ";
        return $iframe;
    }
}
