<?php

namespace Alma\MonthlyPayments\Helpers;

use Magento\Store\Model\StoreManagerInterface;

class CmsFeaturesDataHelper
{
    private $configHelper;
    private $config;
    private $storeManager;

    /**
     * @param ConfigHelper $configHelper
     * @param WidgetConfigHelper $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ConfigHelper          $configHelper,
        WidgetConfigHelper    $config,
        StoreManagerInterface $storeManager
    )
    {
        $this->configHelper = $configHelper;
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * Return data for CmsFeatures Object
     *
     * @return array
     */
    public function getCmsFeaturesData(): array
    {
        return [
            'alma_enabled' => $this->config->getIsActive(),
            'widget_cart_activated' => $this->config->showEligibilityMessage(),
            'widget_product_activated' => $this->config->showProductWidget(),
            'custom_widget_css' => $this->convertStringToBool($this->config->isCustomWidgetPosition()),
            'used_fee_plans' => $this->getFeePlans(),
            'in_page_activated' => $this->configHelper->isInPageEnabled(),
            'log_activated' => (bool)(int)$this->configHelper->getConfigByCode(Logger::CONFIG_DEBUG),
            'excluded_categories' => $this->config->getExcludedProductTypes(),
            'payment_method_position' => $this->config->getSortOrder(),
            'specific_features' => $this->configHelper->getConfigByCode('alma_merge_payment') ? ['Merged Payment Methods'] : [],
            'country_restriction' => [],
            'is_multisite' => !$this->storeManager->hasSingleStore(),
        ];
    }

    /**
     * Get fee plans from config or empty array
     *
     * @return array
     */
    private function getFeePlans(): array
    {
        return json_decode($this->config->getPaymentPlansConfig()->toJson(), true);
    }

    private function convertStringToBool($stringValue): bool
    {
        if ($stringValue === 'true' || $stringValue === 'false') {
            return $stringValue === 'true';
        }
        return false;
    }
}
