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
                   <script type='module' src='https://protect.staging.almapay.com/openInPageModal.js'></script>
                   <script>
                       var btnSave = document.getElementById('save')
                       btnSave.addEventListener('click', function (e) {
                           e.preventDefault();
                           var widgetData = getAlmaWidgetData().then((data) => {
                           document.getElementById('alma_insurance_config').value = JSON.stringify(widgetData)
                            })
                       })
                   </script>
                   <input id='alma_insurance_config' name='groups[alma_insurance][fields][alma_insurance_config][value]' type='text' value='' />
                   ";
        return $iframe;
    }
}
