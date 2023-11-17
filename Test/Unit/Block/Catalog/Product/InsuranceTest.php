<?php

namespace Alma\MonthlyPayments\Test\Unit\Block\Catalog\Product;

use Alma\MonthlyPayments\Block\Catalog\Product\Insurance;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Json\EncoderInterface as jsonEncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
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

    protected function setUp() : void
    {
        $this->contextMock = $this->createMock(Context::class);
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
            $this->apiConfigHelper
        ];
    }
    protected function createNewInsuranceBlock() : Insurance
    {
        return new Insurance(...$this->getConstructorDependency());
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
     * @dataProvider urlDependingModeDataProvider
     * @return void
     */
    public function testReturnExpectedUrlDependingMode($activeMode, $expectedUrl, $type):void
    {
        $this->apiConfigHelper->method('getActiveMode')->willReturn($activeMode);
        $this->assertEquals($expectedUrl, $this->insuranceBlock->getIframeUrl($type));
    }

    private function urlDependingModeDataProvider(): array
    {
        return [
            'Return sandbox front widget Url for sandbox Mode' => [
                'activeMode' => ApiConfigHelper::TEST_MODE_KEY,
                'expectedUrl' => 'https://protect.staging.almapay.com/almaProductInPageWidget.html',
                'type' => 'frontWidget'
            ],
            'Return Prod front widget Url for Prod Mode' => [
                'activeMode' => ApiConfigHelper::LIVE_MODE_KEY,
                'expectedUrl' => 'https://protect.almapay.com/almaProductInPageWidget.html',
                'type' => 'frontWidget'
            ],
            'Return sandbox script Url for sandbox Mode' => [
                'activeMode' => ApiConfigHelper::TEST_MODE_KEY,
                'expectedUrl' => 'https://protect.staging.almapay.com/openInPageModal.js',
                'type' => 'script'
            ],
            'Return Prod script Url for Prod Mode' => [
                'activeMode' => ApiConfigHelper::LIVE_MODE_KEY,
                'expectedUrl' => 'https://protect.almapay.com/openInPageModal.js',
                'type' => 'script'
            ],
        ];
    }
}
