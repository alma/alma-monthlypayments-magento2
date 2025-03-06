<?php

namespace Alma\MonthlyPayments\Observer\Admin;

use Alma\API\Lib\IntegrationsConfigurationsUtils;
use Alma\MonthlyPayments\Helpers\Availability;
use Alma\MonthlyPayments\Helpers\CollectCmsConfigHelper;
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
    private $logger;
    private $availability;
    private $configHelper;
    private $storeHelper;
    private $cacheManager;
    /**
     * @var CollectCmsConfigHelper
     */
    private $collectCmsConfigHelper;

    public function __construct(
        Logger                 $logger,
        UrlInterface           $url,
        PaymentPlansHelper     $paymentPlansHelper,
        Availability           $availability,
        ConfigHelper           $configHelper,
        StoreHelper            $storeHelper,
        Manager                $cacheManager,
        CollectCmsConfigHelper $collectCmsConfigHelper
    ) {
        $this->url = $url;
        $this->paymentPlansHelper = $paymentPlansHelper;
        $this->logger = $logger;
        $this->availability = $availability;
        $this->configHelper = $configHelper;
        $this->storeHelper = $storeHelper;
        $this->cacheManager = $cacheManager;
        $this->collectCmsConfigHelper = $collectCmsConfigHelper;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer): self
    {
        $controller = $observer->getEvent()->getControllerAction();
        $currentUrl = $this->url->getCurrentUrl();
        if (preg_match('!section\/(alma_insurance_section|payment)!', $currentUrl, $matches)) {
            if ($matches[1] === 'payment') {

                $this->paymentPlansHelper->saveBaseApiPlansConfig();
                # Send the data collect URL to Alma if necessary
                if (IntegrationsConfigurationsUtils::isUrlRefreshRequired((int)$this->collectCmsConfigHelper->getSendCollectUrlStatus())) {
                    $this->collectCmsConfigHelper->sendIntegrationsConfigurationsUrl();
                }
            }
            try {
                $cmsInsuranceFlagValue = $this->availability->isMerchantInsuranceAvailable();
            } catch (AlmaInsuranceFlagException $e) {
                $this->logger->error('Alma Insurance Flag Exception', ['exception' => $e->getMessage()]);
                return $this;
            }
            $cacheTypeList = ["config", "layout", "block_html", "compiled_config", "config_integration", "config_integration_api", "full_page"];
            if (!$cmsInsuranceFlagValue && $this->configHelper->getConfigByCode(InsuranceHelper::IS_ALLOWED_INSURANCE_PATH) === '1') {

                $this->saveIsAllowedInsurance(0);
                $this->getClearInsuranceConfig();
                $this->cacheManager->clean($cacheTypeList);
                $controller->getResponse()->setRedirect($currentUrl);
            }
            if ($cmsInsuranceFlagValue && $this->configHelper->getConfigByCode(InsuranceHelper::IS_ALLOWED_INSURANCE_PATH) === '0') {
                $this->saveIsAllowedInsurance(1);
                $this->cacheManager->clean($cacheTypeList);
                $controller->getResponse()->setRedirect($currentUrl);
            }

        }
        return $this;
    }

    /**
     * @param $value
     * @return void
     */
    private function saveIsAllowedInsurance($value): void
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
