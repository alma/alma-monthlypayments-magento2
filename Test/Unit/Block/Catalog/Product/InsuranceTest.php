<?php

namespace Alma\MonthlyPayments\Test\Unit\Block\Catalog\Product;

use Alma\MonthlyPayments\Block\Catalog\Product\Insurance;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Data\InsuranceConfig;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Json\EncoderInterface as jsonEncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Url\EncoderInterface;
use PHPUnit\Framework\TestCase;

class InsuranceTest extends TestCase
{
    /**
     * @var Context
     */
    private $contextMock;
    /**
     * @var EncoderInterface
     */
    private $urlEncoder;
    /**
     * @var jsonEncoderInterface
     */
    private $jsonEncoderInterface;
    /**
     * @var StringUtils
     */
    private $stingUtils;
    /**
     * @var Product
     */
    private $productHelper;
    /**
     * @var ConfigInterface
     */
    private $configInteface;
    /**
     * @var FormatInterface
     */
    private $formatInterface;
    /**
     * @var Session
     *
     */
    private $customerSession;
    /**
     * @var ProductRepositoryInterface
     *
     */
    private $productRepositoryInterface;
    /**
     * @var PriceCurrencyInterface
     *
     */
    private $priceCurrencyInterface;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var InsuranceHelper
     *
     */
    private $insuranceHelper;
    /**
     * @var Insurance
     */
    private $insuranceBlock;
    /**
     * @var ApiConfigHelper
     *
     */
    private $apiConfigHelper;
    /**
     * @var Config
     *
     */
    private $config;

    protected function setUp() : void
    {
        $this->contextMock = $this->createMock(Context::class);
        $productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $productMock->method('getSku')->willReturn('mysku');
        $productMock->method('getPrice')->willReturn(100.10);
        $registry = $this->createMock(Registry::class);
        $registry->method('registry')->willReturn($productMock);
        $this->contextMock->method('getRegistry')->willReturn($registry);
        $this->urlEncoder = $this->createMock(EncoderInterface::class);
        $this->jsonEncoderInterface = $this->createMock(jsonEncoderInterface::class);
        $this->stingUtils = $this->createMock(StringUtils::class);
        $this->productHelper = $this->createMock(Product::class);
        $this->configInteface = $this->createMock(ConfigInterface::class);
        $this->formatInterface = $this->createMock(FormatInterface::class);
        $this->customerSession = $this->createMock(Session::class);
        $this->productRepositoryInterface = $this->createMock(ProductRepositoryInterface::class);
        $this->priceCurrencyInterface = $this->createMock(PriceCurrencyInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->insuranceHelper = $this->createMock(InsuranceHelper::class);
        $this->apiConfigHelper = $this->createMock(ApiConfigHelper::class);
        $this->config = $this->createMock(Config::class);
        $this->config->method('getMerchantId')->willReturn('merchant_123456');
        $this->insuranceBlock = $this->createNewInsuranceBlock();
    }
    protected function getConstructorDependency():array
    {
        return [
            $this->contextMock,
            $this->urlEncoder,
            $this->jsonEncoderInterface,
            $this->stingUtils,
            $this->productHelper,
            $this->configInteface,
            $this->formatInterface,
            $this->customerSession,
            $this->productRepositoryInterface,
            $this->priceCurrencyInterface,
            $this->logger,
            $this->insuranceHelper,
            $this->apiConfigHelper,
            $this->config
        ];
    }
    protected function createNewInsuranceBlock() : Insurance
    {
        return new Insurance(...$this->getConstructorDependency());
    }
    public function testReturnFalseIfInsuranceIsDisallow():void
    {
        $insuranceConfig = $this->createMock(InsuranceConfig::class);
        $insuranceConfig->method('isAllowed')->willReturn(false);
        $insuranceConfig->method('isPageActivated')->willReturn(true);
        $this->insuranceHelper->method('getConfig')->willReturn($insuranceConfig);
        $this->assertFalse($this->insuranceBlock->isActivatedWidgetInProductPage());
    }
    public function testReturnFalseIfInsuranceIsAllowedAndPageNotActivated():void
    {
        $insuranceConfig = $this->createMock(InsuranceConfig::class);
        $insuranceConfig->method('isAllowed')->willReturn(true);
        $insuranceConfig->method('isPageActivated')->willReturn(false);
        $this->insuranceHelper->method('getConfig')->willReturn($insuranceConfig);
        $this->assertFalse($this->insuranceBlock->isActivatedWidgetInProductPage());
    }
    public function testReturnTrueIfInsuranceIsAllowedAndPageActivated():void
    {
        $insuranceConfig = $this->createMock(InsuranceConfig::class);
        $insuranceConfig->method('isAllowed')->willReturn(true);
        $insuranceConfig->method('isPageActivated')->willReturn(true);
        $this->insuranceHelper->method('getConfig')->willReturn($insuranceConfig);
        $this->assertTrue($this->insuranceBlock->isActivatedWidgetInProductPage());
    }

    public function testReturnPopUpDisplayFalseIfInsuranceIsDisallow():void
    {
        $insuranceConfig = $this->createMock(InsuranceConfig::class);
        $insuranceConfig->method('isAllowed')->willReturn(false);
        $insuranceConfig->method('isPopupActivated')->willReturn(true);
        $this->insuranceHelper->method('getConfig')->willReturn($insuranceConfig);
        $this->assertFalse($this->insuranceBlock->isActivatedWidgetInProductPage());
    }
    public function testIsStringIframeUrl():void
    {
        $this->assertTrue(gettype($this->insuranceBlock->getIframeUrl()) === 'string');
    }
    public function testCallConfigToKnowMode():void
    {
        $this->apiConfigHelper->expects($this->once())->method('getActiveMode');
        $this->insuranceBlock->getIframeUrl();
    }

    /**
     * @param $activeMode
     * @param $expectedUrl
     * @dataProvider iframeUrlDependingMode
     * @return void
     */
    public function testReturnExpectedUrlDependingMode($activeMode, $expectedUrl):void
    {
        $this->config->expects($this->once())->method('getMerchantId')->willReturn('');
        $this->apiConfigHelper->method('getActiveMode')->willReturn($activeMode);
        $this->assertEquals($expectedUrl, $this->insuranceBlock->getIframeUrl());
    }

    private function iframeUrlDependingMode(): array
    {
        return [
            'Return sandbox front widget Url for sandbox Mode' => [
                'activeMode' => ApiConfigHelper::TEST_MODE_KEY,
                'expectedUrl' => 'https://protect.staging.almapay.com/almaProductInPageWidget.html?merchant_id=merchant_123456&cms_reference=mysku&product_price=10010',
            ],
            'Return Prod front widget Url for Prod Mode' => [
                'activeMode' => ApiConfigHelper::LIVE_MODE_KEY,
                'expectedUrl' => 'https://protect.almapay.com/almaProductInPageWidget.html?merchant_id=merchant_123456&cms_reference=mysku&product_price=10010',
            ],
        ];
    }
    /**
     * @param $activeMode
     * @param $expectedUrl
     * @dataProvider scriptUrlDependingMode
     * @return void
     */
    public function testGetScriptUrlDependingMode($activeMode, $expectedUrl):void
    {
        $this->apiConfigHelper->method('getActiveMode')->willReturn($activeMode);
        $this->assertEquals($expectedUrl, $this->insuranceBlock->getScriptUrl());
    }

    private function scriptUrlDependingMode(): array
    {
        return [
            'Return sandbox script Url for sandbox Mode' => [
                'activeMode' => ApiConfigHelper::TEST_MODE_KEY,
                'expectedUrl' => 'https://protect.staging.almapay.com/displayModal.js',
            ],
            'Return Prod script Url for Prod Mode' => [
                'activeMode' => ApiConfigHelper::LIVE_MODE_KEY,
                'expectedUrl' => 'https://protect.almapay.com/displayModal.js',
            ],
        ];
    }
}
