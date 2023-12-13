<?php

namespace Alma\MonthlyPayments\Block\Catalog\Product;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View as ProductView;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Json\EncoderInterface as jsonEncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface as PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Url\EncoderInterface;

class Insurance extends ProductView
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var InsuranceHelper
     */
    private $insuranceHelper;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Context $context
     * @param EncoderInterface $urlEncoder
     * @param jsonEncoderInterface $jsonEncoder
     * @param StringUtils $string
     * @param Product $productHelper
     * @param ConfigInterface $productTypeConfig
     * @param FormatInterface $localeFormat
     * @param Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param Logger $logger
     * @param InsuranceHelper $insuranceHelper
     * @param array $data
     */
    public function __construct(
        Context                    $context,
        EncoderInterface           $urlEncoder,
        jsonEncoderInterface       $jsonEncoder,
        StringUtils                $string,
        Product                    $productHelper,
        ConfigInterface            $productTypeConfig,
        FormatInterface            $localeFormat,
        Session                    $customerSession,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface     $priceCurrency,
        Logger                     $logger,
        InsuranceHelper            $insuranceHelper,
        ApiConfigHelper            $apiConfigHelper,
        Config                     $config,
        array                      $data = []
    ) {
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );
        $this->logger = $logger;
        $this->insuranceHelper = $insuranceHelper;
        $this->apiConfigHelper = $apiConfigHelper;
        $this->config = $config;
    }

    public function isActivatedWidgetInProductPage():bool
    {
        $config = $this->insuranceHelper->getConfig();
        return $config->isAllowed() && $config->isPageActivated();
    }

    public function isActivatedPopupInProductPage():bool
    {
        $config = $this->insuranceHelper->getConfig();
        return $config->isAllowed() && $config->isPopupActivated();
    }

    public function getIframeUrl():string
    {
        $path = InsuranceHelper::FRONT_IFRAME_PATH;
        $productPrice = $this->getProduct()->getPrice() * 100;
        return $this->getHost() .
            $path . '?' .
            InsuranceHelper::MERCHANT_ID_PARAM_KEY. '=' .$this->config->getMerchantId(). '&' .
            InsuranceHelper::CMS_REF_PARAM_KEY. '=' .$this->getProduct()->getSku() . '&' .
            InsuranceHelper::PRODUCT_PRICE_PARAM_KEY. '=' .$productPrice;
    }

    public function getScriptUrl(): string
    {
        $path = InsuranceHelper::SCRIPT_IFRAME_PATH;
        return $this->getHost() . $path;
    }

    private function getHost():string
    {
        $host = InsuranceHelper::SANDBOX_IFRAME_HOST_URL;
        $activeMode =  $this->apiConfigHelper->getActiveMode();
        if ($activeMode === ApiConfigHelper::LIVE_MODE_KEY) {
            $host = InsuranceHelper::PRODUCTION_IFRAME_HOST_URL;
        }
        return $host;
    }
}
