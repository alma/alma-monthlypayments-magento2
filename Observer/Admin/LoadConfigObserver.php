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
use Magento\Framework\App\Cache\Manager;
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

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Availability
     */
    private $availability;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var StoreHelper
     */
    private $storeHelper;

    /**
     * @var Manager
     */
    private $cacheManager;

    /**
     * @param Logger $logger
     * @param UrlInterface $url
     * @param PaymentPlansHelper $paymentPlansHelper
     * @param Availability $availability
     * @param ConfigHelper $configHelper
     * @param StoreHelper $storeHelper
     * @param Manager $cacheManager
     */
    public function __construct(
        Logger             $logger,
        UrlInterface       $url,
        PaymentPlansHelper $paymentPlansHelper,
        Availability       $availability,
        ConfigHelper       $configHelper,
        StoreHelper        $storeHelper,
        Manager            $cacheManager
    ) {
        $this->url = $url;
        $this->paymentPlansHelper = $paymentPlansHelper;
        $this->logger = $logger;
        $this->availability = $availability;
        $this->configHelper = $configHelper;
        $this->storeHelper = $storeHelper;
        $this->cacheManager = $cacheManager;
    }

    /**
     * For alma insurance section and payment section, call Alma API to check if merchant feature flag
     *
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
                $cmsInsuranceFlagValue = $this->availability->isMerchantInsuranceAvailable();
            } catch (AlmaInsuranceFlagException $e) {
                $this->logger->error('Alma Insurance Flag Exception', ['exception' => $e->getMessage()]);
                return $this;
            }
            $cacheTypeList = [
                "config",
                "layout",
                "block_html",
                "compiled_config",
                "config_integration",
                "config_integration_api","full_page"
            ];
            if (!$cmsInsuranceFlagValue &&
                $this->configHelper->getConfigByCode(InsuranceHelper::IS_ALLOWED_INSURANCE_PATH) === '1'
            ) {
                $this->saveIsAllowedInsurance(0);
                $this->clearInsuranceConfig();
                $this->cacheManager->clean($cacheTypeList);
                $controller->getResponse()->setRedirect($currentUrl);
            }
            if ($cmsInsuranceFlagValue &&
                $this->configHelper->getConfigByCode(InsuranceHelper::IS_ALLOWED_INSURANCE_PATH) === '0'
            ) {
                $this->saveIsAllowedInsurance(1);
                $this->cacheManager->clean($cacheTypeList);
                $controller->getResponse()->setRedirect($currentUrl);
            }
        }
        return $this;
    }

    /**
     * Save is allowed insurance from feature flag
     *
     * @param int $value
     * @return void
     */
    private function saveIsAllowedInsurance(int $value):void
    {
        $this->configHelper->saveIsAllowedInsuranceValue(
            $value,
            $this->storeHelper->getScope(),
            $this->storeHelper->getStoreId()
        );
    }

    /**
     * Clear insurance config
     *
     * @return void
     */
    private function clearInsuranceConfig(): void
    {
        $this->configHelper->clearInsuranceConfig(
            $this->storeHelper->getScope(),
            $this->storeHelper->getStoreId()
        );
    }
}
