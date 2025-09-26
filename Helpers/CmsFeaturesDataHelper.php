<?php

namespace Alma\MonthlyPayments\Helpers;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Api\Data\PaymentMethodInterface;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class CmsFeaturesDataHelper
{
    private $configHelper;
    private $config;
    private $storeManager;
    private $paymentMethodList;
    private $scopeConfig;
    private $logger;

    /**
     * @param ConfigHelper $configHelper
     * @param WidgetConfigHelper $config
     * @param StoreManagerInterface $storeManager
     * @param PaymentMethodListInterface $paymentMethodList
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     */
    public function __construct(
        ConfigHelper               $configHelper,
        WidgetConfigHelper         $config,
        StoreManagerInterface      $storeManager,
        PaymentMethodListInterface $paymentMethodList,
        ScopeConfigInterface       $scopeConfig,
        Logger                     $logger
    ) {
        $this->configHelper = $configHelper;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->paymentMethodList = $paymentMethodList;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
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
            'custom_widget_css' => $this->config->isCustomWidgetPosition() === 'true',
            'used_fee_plans' => $this->getFeePlans(),
            'in_page_activated' => $this->configHelper->isInPageEnabled(),
            'log_activated' => (bool)(int)$this->configHelper->getConfigByCode(Logger::CONFIG_DEBUG),
            'excluded_categories' => $this->config->getExcludedProductTypes(),
            'payment_methods_list' => !empty($this->sortAndRenumberPaymentGateways()) ? $this->sortAndRenumberPaymentGateways() : null,
            'payment_method_position' => $this->config->getSortOrder(),
            'specific_features' => $this->configHelper->getConfigByCode('alma_merge_payment') ? ['Merged Payment Methods'] : [],
            'country_restriction' => [],
            'is_multisite' => !$this->storeManager->hasSingleStore(),
        ];
    }

    /**
     * Get fee plans from config or empty array
     *
     * @return array | null
     */
    private function getFeePlans(): ?array
    {
        $feePlans = json_decode($this->config->getPaymentPlansConfig()->toJson(), true);
        return empty($feePlans) ? null : $feePlans;
    }

    function sortAndRenumberPaymentGateways(): array
    {
        try {
            $storeId = $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Could not get store ID: ' . $e->getMessage());
            return ['name' => 'errorToGetPaymentMethodsList', 'position' => 1];
        }
        $activePaymentMethods = $this->paymentMethodList->getActiveList($storeId);
        $gatewaysList = [];
        /** @var PaymentMethodInterface $paymentMethod */
        foreach ($activePaymentMethods as $paymentMethod) {
            $code = $paymentMethod->getCode();
            $sortOrder = $this->scopeConfig->getValue(
                "payment/{$code}/sort_order",
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $gatewaysList[] = [
                'name' => $paymentMethod->getCode(),
                'position' => (int)$sortOrder,
            ];
        }
        usort($gatewaysList, function ($a, $b) {
            $positionComparison = $a['position'] <=> $b['position'];

            if ($positionComparison === 0) {
                return strcasecmp($a['name'], $b['name']);
            }

            return $positionComparison;
        });

        $renumberedGateways = [];
        foreach ($gatewaysList as $index => $gateway) {
            $renumberedGateways[] = [
                'name' => $gateway['name'],
                'position' => $index + 1
            ];
        }
        return $renumberedGateways;
    }
}
