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
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\ConfigurableProduct\Helper\Data;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * @var Data
     */
    private $configurableHelper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

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
        Context                         $context,
        EncoderInterface                $urlEncoder,
        jsonEncoderInterface            $jsonEncoder,
        StringUtils                     $string,
        Product                         $productHelper,
        ConfigInterface                 $productTypeConfig,
        FormatInterface                 $localeFormat,
        Session                         $customerSession,
        ProductRepositoryInterface      $productRepository,
        PriceCurrencyInterface          $priceCurrency,
        Logger                          $logger,
        InsuranceHelper                 $insuranceHelper,
        ApiConfigHelper                 $apiConfigHelper,
        Config                          $config,
        Data                            $configurableHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        array                           $data = []
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
        $this->configurableHelper = $configurableHelper;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
    }

    public function isActivatedWidgetInProductPage(): bool
    {
        $config = $this->insuranceHelper->getConfig();
        return $config->isAllowed() && $config->isPageActivated();
    }

    public function isActivatedPopupInProductPage(): bool
    {
        $config = $this->insuranceHelper->getConfig();
        return $config->isAllowed() && $config->isPopupActivated();
    }

    public function getIframeUrl(): string
    {
        $path = InsuranceHelper::FRONT_IFRAME_PATH;
        return $this->getHost() .
            $path . '?' .
            InsuranceHelper::CMS_REF_PARAM_KEY . '=' . $this->getBaseProductSku() . '&' .
            InsuranceHelper::PRODUCT_PRICE_PARAM_KEY . '=' . $this->getProductPriceInCent() . '&' .
            InsuranceHelper::MERCHANT_ID_PARAM_KEY . '=' . $this->getMerchantId() . '&' .
            InsuranceHelper::CUSTOMER_SESSION_ID_PARAM_KEY . '=' . $this->getCustomerSessionId() . '&' .
            InsuranceHelper::CUSTOMER_CART_ID_PARAM_KEY . '=' . $this->getQuoteId();
    }

    public function getScriptUrl(): string
    {
        $path = InsuranceHelper::SCRIPT_IFRAME_PATH;
        return $this->getHost() . $path;
    }

    private function getHost(): string
    {
        $host = InsuranceHelper::SANDBOX_IFRAME_HOST_URL;
        $activeMode = $this->apiConfigHelper->getActiveMode();
        if ($activeMode === ApiConfigHelper::LIVE_MODE_KEY) {
            $host = InsuranceHelper::PRODUCTION_IFRAME_HOST_URL;
        }
        return $host;
    }

    /**
     * @return string|null
     */
    public function getCustomerSessionId(): ?string
    {
        return $this->customerSession->getSessionId();
    }

    /**
     * @return string|null
     */
    public function getQuoteId(): ?string
    {
        return $this->checkoutSession->getQuoteId();
    }

    public function getMerchantId(): string
    {
        return $this->config->getMerchantId();
    }

    public function getProductPriceInCent(): int
    {
        return (int)($this->getProduct()->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE)->getAmount()->getValue() * 100);
    }

    public function getBaseProductSku(): string
    {
        return $this->getProduct()->getSku();
    }

    public function getBaseProductId(): string
    {
        return $this->getProduct()->getId();
    }

    public function getProductName(): string
    {
        return $this->getProduct()->getName();
    }

    public function getProductChild(): string
    {
        $childProducts = $this->getProduct()->getTypeInstance()->getUsedProducts($this->getProduct());
        $arrayOptions = $this->configurableHelper->getOptions($this->getProduct(), $childProducts);
        $productsAttributes = [];
        foreach ($arrayOptions['index'] as $id => $options) {
            try {
                $product = $this->productRepository->getById($id);
            } catch (NoSuchEntityException $e) {
                $this->logger->error('NoSuchEntityException in get Child Product', [$e->getMessage()]);
            }
            $productsAttributes[] = ['sku' => $product->getSku(), 'attributes' => $options];
        }
        return json_encode($productsAttributes);
    }
}
