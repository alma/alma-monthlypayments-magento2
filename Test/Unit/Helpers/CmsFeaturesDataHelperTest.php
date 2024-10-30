<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterface;
use Alma\MonthlyPayments\Helpers\CmsFeaturesDataHelper;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\WidgetConfigHelper;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class CmsFeaturesDataHelperTest extends TestCase
{
    private $configHelper;
    private $config;
    private $storeManager;
    private $cmsFeaturesDataHelper;
    private $paymentPlansConfigInterface;

    protected function setUp(): void
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->paymentPlansConfigInterface = $this->createMock(PaymentPlansConfigInterface::class);
        $this->config = $this->createMock(WidgetConfigHelper::class);
        $this->config->method('getPaymentPlansConfig')->willReturn($this->paymentPlansConfigInterface);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->cmsFeaturesDataHelper = new CmsFeaturesDataHelper(
            $this->configHelper,
            $this->config,
            $this->storeManager
        );
    }

    public function testDefaultOutput(): void
    {
        $this->paymentPlansConfigInterface->method('toJson')->willReturn('[]');

        $this->assertEquals(
            [
                'alma_enabled' => false,
                'widget_cart_activated' => false,
                'widget_product_activated' => false,
                'custom_widget_css' => false,
                'used_fee_plans' => [],
                'in_page_activated' => false,
                'log_activated' => false,
                'excluded_categories' => null,
                'payment_method_position' => 0,
                'specific_features' => [],
                'country_restriction' => [],
                'is_multisite' => true,
            ],
            $this->cmsFeaturesDataHelper->getCmsFeaturesData());
    }
    public function testSpecificFeatureOutput(): void
    {
        $this->paymentPlansConfigInterface->method('toJson')->willReturn(json_encode(['key'=>'value'], true));
        $this->configHelper->method('getConfigByCode')->willReturnMap(
            [
                [Logger::CONFIG_DEBUG,null ,null , '1'],
                ['alma_merge_payment',null ,null , '1'],
            ]
        );
        $this->config->method('isCustomWidgetPosition')->willReturn('true');
        $this->storeManager->method('hasSingleStore')->willReturn(true);
        $this->assertEquals(
            [
                'alma_enabled' => false,
                'widget_cart_activated' => false,
                'widget_product_activated' => false,
                'custom_widget_css' => true,
                'used_fee_plans' => ['key'=>'value'],
                'in_page_activated' => false,
                'log_activated' => true,
                'excluded_categories' => null,
                'payment_method_position' => 0,
                'specific_features' => ['Merged Payment Methods'],
                'country_restriction' => [],
                'is_multisite' => false,
            ],
            $this->cmsFeaturesDataHelper->getCmsFeaturesData());
    }
}
