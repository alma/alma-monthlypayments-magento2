<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\Insurance;

use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Magento\Backend\Block\Template;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class SubscriptionDetails extends Template
{
    /**
     * @var InsuranceHelper
     */
    private $insuranceHelper;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;

    public function __construct(
        Template\Context $context,
        InsuranceHelper  $insuranceHelper,
        ApiConfigHelper  $apiConfigHelper,
        array            $data = [],
        ?JsonHelper      $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    )
    {
        parent::__construct(
            $context,
            $data,
            $jsonHelper,
            $directoryHelper
        );
        $this->insuranceHelper = $insuranceHelper;
        $this->apiConfigHelper = $apiConfigHelper;
    }

    /**
     * @return string
     */
    public function getScriptUrl():string
    {
        return 'https://protect.sandbox.almapay.com/displayModal.js';
        //return $this->insuranceHelper->getScriptUrl($this->apiConfigHelper->getActiveMode());
    }

    public function getCoucou()
    {
        return 'coucou';
    }
}
