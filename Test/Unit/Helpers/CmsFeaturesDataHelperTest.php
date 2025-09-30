<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterface;
use Alma\MonthlyPayments\Helpers\CmsFeaturesDataHelper;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\WidgetConfigHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Api\Data\PaymentMethodInterface;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class CmsFeaturesDataHelperTest extends TestCase
{
    private $configHelper;
    private $config;
    private $storeManager;
    private $cmsFeaturesDataHelper;
    private $paymentPlansConfigInterface;
    private $paymentMethodList;
    private $scopeConfig;
    private $logger;

    protected function setUp(): void
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->paymentPlansConfigInterface = $this->createMock(PaymentPlansConfigInterface::class);
        $this->config = $this->createMock(WidgetConfigHelper::class);
        $this->config->method('getPaymentPlansConfig')->willReturn($this->paymentPlansConfigInterface);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $storeInterface = $this->createMock(StoreInterface::class);
        $storeInterface->method('getId')->willReturn(1);
        $this->storeManager->method('getStore')->willReturn($storeInterface);
        $this->paymentMethodList = $this->createMock(PaymentMethodListInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->cmsFeaturesDataHelper = new CmsFeaturesDataHelper(
            $this->configHelper,
            $this->config,
            $this->storeManager,
            $this->paymentMethodList,
            $this->scopeConfig,
            $this->logger
        );
    }

    public function testDefaultOutput(): void
    {
        $this->paymentPlansConfigInterface->method('toJson')->willReturn('[]');
        $this->paymentMethodList->method('getActiveList')->willReturn([]);

        $this->assertEquals(
            [
                'alma_enabled' => false,
                'widget_cart_activated' => false,
                'widget_product_activated' => false,
                'custom_widget_css' => false,
                'used_fee_plans' => null,
                'in_page_activated' => false,
                'log_activated' => false,
                'excluded_categories' => null,
                'payment_methods_list' => null,
                'payment_method_position' => 0,
                'specific_features' => [],
                'country_restriction' => [],
                'is_multisite' => true,
            ],
            $this->cmsFeaturesDataHelper->getCmsFeaturesData());
    }

    public function testSpecificFeatureOutput(): void
    {
        $this->paymentPlansConfigInterface->method('toJson')->willReturn(json_encode(['key' => 'value'], true));
        $this->paymentMethodList->method('getActiveList')->willReturn([]);
        $this->configHelper->method('getConfigByCode')->willReturnMap(
            [
                [Logger::CONFIG_DEBUG, null, null, '1'],
                ['alma_merge_payment', null, null, '1'],
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
                'used_fee_plans' => ['key' => 'value'],
                'in_page_activated' => false,
                'log_activated' => true,
                'excluded_categories' => null,
                'payment_methods_list' => null,
                'payment_method_position' => 0,
                'specific_features' => ['Merged Payment Methods'],
                'country_restriction' => [],
                'is_multisite' => false,
            ],
            $this->cmsFeaturesDataHelper->getCmsFeaturesData());
    }

    /**
     * @dataProvider paymentMethodListDataProvider
     */
    public function testPaymentMethodListWithDefinedPositions($result, $valueMap): void
    {
        $this->paymentPlansConfigInterface->method('toJson')->willReturn('[]');

        $this->scopeConfig
            ->method('getValue')
            ->willReturnMap($valueMap);

        $almaPaymentMethod = $this->createMock(PaymentMethodInterface::class);
        $almaPaymentMethod->method('getCode')->willReturn('alma');
        $paypalPaymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paypalPaymentMethod->method('getCode')->willReturn('paypal');
        $checkmoPaymentMethod = $this->createMock(PaymentMethodInterface::class);
        $checkmoPaymentMethod->method('getCode')->willReturn('checkmo');
        $freePaymentMethod = $this->createMock(PaymentMethodInterface::class);
        $freePaymentMethod->method('getCode')->willReturn('free');
        $this->paymentMethodList->method('getActiveList')->willReturn([$freePaymentMethod, $almaPaymentMethod, $paypalPaymentMethod, $checkmoPaymentMethod]);


        $this->assertEquals(
            [
                'alma_enabled' => false,
                'widget_cart_activated' => false,
                'widget_product_activated' => false,
                'custom_widget_css' => false,
                'used_fee_plans' => null,
                'in_page_activated' => false,
                'log_activated' => false,
                'excluded_categories' => null,
                'payment_methods_list' => $result,
                'payment_method_position' => 0,
                'specific_features' => [],
                'country_restriction' => [],
                'is_multisite' => true,
            ],
            $this->cmsFeaturesDataHelper->getCmsFeaturesData());
    }

    public function testStoreThrowException()
    {
        $this->storeManager->method('getStore')->willThrowException(new NoSuchEntityException());
        $this->paymentPlansConfigInterface->method('toJson')->willReturn('[]');
        $this->assertEquals(
            [
                'alma_enabled' => false,
                'widget_cart_activated' => false,
                'widget_product_activated' => false,
                'custom_widget_css' => false,
                'used_fee_plans' => null,
                'in_page_activated' => false,
                'log_activated' => false,
                'excluded_categories' => null,
                'payment_methods_list' => ['name' => 'errorToGetPaymentMethodsList', 'position' => 1],
                'payment_method_position' => 0,
                'specific_features' => [],
                'country_restriction' => [],
                'is_multisite' => true,
            ],
            $this->cmsFeaturesDataHelper->getCmsFeaturesData());

    }

    private function paymentMethodListDataProvider()
    {
        return [
            "With defined Positions" => [
                'result' => [['name' => 'checkmo', 'position' => 1], ['name' => 'paypal', 'position' => 2], ['name' => 'alma', 'position' => 3], ['name' => 'free', 'position' => 4]],
                'SortOrder' => [
                    ['payment/free/sort_order', ScopeInterface::SCOPE_STORE, 1, 2],
                    ['payment/checkmo/sort_order', ScopeInterface::SCOPE_STORE, 1, 0],
                    ['payment/paypal/sort_order', ScopeInterface::SCOPE_STORE, 1, 0],
                    ['payment/alma/sort_order', ScopeInterface::SCOPE_STORE, 1, 1],
                ]
            ],
            "Without defined Positions" => [
                'result' => [['name' => 'alma', 'position' => 1], ['name' => 'checkmo', 'position' => 2], ['name' => 'free', 'position' => 3], ['name' => 'paypal', 'position' => 4]],
                'SortOrder' => [
                    ['payment/free/sort_order', ScopeInterface::SCOPE_STORE, 1, 0],
                    ['payment/checkmo/sort_order', ScopeInterface::SCOPE_STORE, 1, 0],
                    ['payment/paypal/sort_order', ScopeInterface::SCOPE_STORE, 1, 0],
                    ['payment/alma/sort_order', ScopeInterface::SCOPE_STORE, 1, 0],
                ]
            ],
        ];
    }
}
