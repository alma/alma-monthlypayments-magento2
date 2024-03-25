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
use Magento\Config\Controller\Adminhtml\System\Config\Edit;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

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
        StoreHelper        $storeHelper,
    ) {
        $this->url = $url;
        $this->paymentPlansHelper = $paymentPlansHelper;
        $this->logger = $logger;
        $this->availability = $availability;
        $this->configHelper = $configHelper;
        $this->storeHelper = $storeHelper;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer) : self
    {
        $controller = $observer->getEvent()->getControllerAction();
        $currentUrl = $this->url->getCurrentUrl();
        if (preg_match('!section\/(alma_insurance_section|payment)!', $currentUrl, $matches)) {
            if ($matches[1] === 'payment') {

                $this->paymentPlansHelper->saveBaseApiPlansConfig();
            }
            try {
                $cmsInsuranceFlagValue = $this->availability->getMerchantInsuranceAvailability();
            } catch (AlmaInsuranceFlagException $e) {
                return $this;
            }
            if (!$cmsInsuranceFlagValue && $this->configHelper->getConfigByCode(InsuranceHelper::IS_ALLOWED_INSURANCE_PATH) === '1') {

                $this->saveIsAllowedInsurance(0);
                $this->getClearInsuranceConfig();
                $controller->getResponse()->setRedirect($currentUrl);
            }
            if ($cmsInsuranceFlagValue && $this->configHelper->getConfigByCode(InsuranceHelper::IS_ALLOWED_INSURANCE_PATH) === '0') {

                $this->saveIsAllowedInsurance(1);
                $controller->getResponse()->setRedirect($currentUrl);
            }

        }
        return $this;
    }

    /**
     * @param $value
     * @return void
     */
    private function saveIsAllowedInsurance($value):void
    {
        $this->configHelper->saveIsAllowedInsuranceValue(
            $value,
            $this->storeHelper->getScope(),
            $this->storeHelper->getStoreId()
        );
    }

    /**
     * @return void
     */
    private function getClearInsuranceConfig(): void
    {
        $this->configHelper->clearInsuranceConfig(
            $this->storeHelper->getScope(),
            $this->storeHelper->getStoreId()
        );
    }

}
