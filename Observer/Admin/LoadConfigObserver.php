<?php

namespace Alma\MonthlyPayments\Observer\Admin;

use Alma\MonthlyPayments\Helpers\Availability;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Alma\MonthlyPayments\Helpers\StoreHelper;
use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceFlagException;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManager;

class LoadConfigObserver implements ObserverInterface
{
    /**
     * @var UrlInterface
     */
    private $url;
    /**
     * @var PaymentPlansHelper
     */
    private $paymentPlansHelper;
    private $logger;
    private $availability;
    private $configHelper;
    private $storeHelper;

    public function __construct(
        Logger             $logger,
        UrlInterface       $url,
        PaymentPlansHelper $paymentPlansHelper,
        Availability       $availability,
        ConfigHelper       $configHelper,
        StoreHelper        $storeHelper
    )
    {
        $this->url = $url;
        $this->paymentPlansHelper = $paymentPlansHelper;
        $this->logger = $logger;
        $this->availability = $availability;
        $this->configHelper = $configHelper;
        $this->storeHelper = $storeHelper;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws AlmaInsuranceFlagException
     */
    public function execute(Observer $observer): void
    {
        if (preg_match('!section\/(alma_insurance_section|payment)!', $this->url->getCurrentUrl(), $matches)) {
            $cmsInsuranceFlagValue = $this->availability->getMerchantInsuranceAvailability();
            if(!$cmsInsuranceFlagValue && $this->configHelper->getConfigByCode(InsuranceHelper::IS_ALLOWED_INSURANCE_PATH) === '1'){
                // Hide immediately the insurance section if the merchant is not allowed to use it
                $this->configHelper->saveIsAllowedInsuranceValue(
                    0,
                    $this->storeHelper->getScope(),
                    $this->storeHelper->getStoreId()
                );
                $this->configHelper->clearInsuranceConfig(
                    $this->storeHelper->getScope(),
                    $this->storeHelper->getStoreId()
                );
                //TODO REDIRECT TO CURRENT PAGE FOR RELOAD PAGE WITHOUT CONFIG ENABLED
            }
            if ($matches[1] === 'payment') {
                $this->paymentPlansHelper->saveBaseApiPlansConfig();
            }
        }
    }
}
