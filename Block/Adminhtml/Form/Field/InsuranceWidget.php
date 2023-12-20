<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\Form\Field;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class InsuranceWidget extends Field
{
    /**
     * @var InsuranceHelper
     */
    private $insuranceHelper;

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        InsuranceHelper $insuranceHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->insuranceHelper = $insuranceHelper;
    }
    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element):string
    {
        $iframeUrl = $this->insuranceHelper->getIframeUrlWithParams();
        $iframe = "<div id='alma-insurance-modal'></div>
                   <iframe id='config-alma-iframe'
                    class='alma-insurance-iframe'
                    width='100%'
                    height='100%'
                    src='" . $iframeUrl . "'>
                   </iframe>
                   <script type='module' src='https://protect.staging.almapay.com/displayModal.js'></script>
                   <script>
                       var btnSave = document.getElementById('save')
                       btnSave.addEventListener('click', function (e) {
                           var inputValue = document.getElementById('alma_insurance_config').value;
                           if (inputValue == 'false'){
                               e.stopImmediatePropagation();
                               getAlmaWidgetData().then((data) => {
                                    document.getElementById('alma_insurance_config').value = JSON.stringify(data)
                                    document.getElementById('save').click();
                               })
                           }
                       })
                   </script>
                   <input id='alma_insurance_config' name='groups[alma_insurance][fields][alma_insurance_config][value]' type='hidden' value='false' />
                   ";
        return $iframe;
    }
}
