<?php

/**
 * 2018 Alma / Nabla SAS
 *
 * THE MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
 * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @author    Alma / Nabla SAS <contact@getalma.eu>
 * @copyright 2018 Alma / Nabla SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 *
 */

namespace Alma\MonthlyPayments\Block\Catalog\Product;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\Functions;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\WidgetConfigHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View as ProductView;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Json\EncoderInterface as JsonEncoderInterfaceAlias;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Url\EncoderInterface;

class View extends ProductView
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Resolver
     */
    private $localeResolver;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;
    /**
     * @var WidgetConfigHelper
     */
    private $widgetConfigHelper;

    /**
     * Construct with parent dependency
     *
     * @param Context $context
     * @param EncoderInterface $urlEncoder
     * @param JsonEncoderInterfaceAlias $jsonEncoder
     * @param StringUtils $string
     * @param Product $productHelper
     * @param ConfigInterface $productTypeConfig
     * @param FormatInterface $localeFormat
     * @param Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param ApiConfigHelper $apiConfigHelper
     * @param WidgetConfigHelper $widgetConfigHelper
     * @param Config $config
     * @param Resolver $localeResolver
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        Context                    $context,
        EncoderInterface           $urlEncoder,
        JsonEncoderInterfaceAlias  $jsonEncoder,
        StringUtils                $string,
        Product                    $productHelper,
        ConfigInterface            $productTypeConfig,
        FormatInterface            $localeFormat,
        Session                    $customerSession,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface     $priceCurrency,
        ApiConfigHelper            $apiConfigHelper,
        WidgetConfigHelper         $widgetConfigHelper,
        Config                     $config,
        Resolver                   $localeResolver,
        Logger                     $logger,
        array $data = []
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
        $this->config = $config;
        $this->localeResolver = $localeResolver;
        $this->apiConfigHelper = $apiConfigHelper;
        $this->widgetConfigHelper = $widgetConfigHelper;
    }

    /**
     * Get enabled fee plans on config
     *
     * @return array
     */
    private function getPlans(): array
    {
        $plans = [];
        foreach ($this->config->getPaymentPlansConfig()->getEnabledPlans() as $planConfig) {
            $plans[] = [
                'installmentsCount' => $planConfig->installmentsCount(),
                'minAmount' => $planConfig->minimumAmount(),
                'maxAmount' => $planConfig->maximumAmount(),
                'deferredDays' => $planConfig->deferredDays(),
                'deferredMonths' => $planConfig->deferredMonths()
            ];
        }
        return $plans;
    }

    /**
     * Check if product type is in config exclusion group
     *
     * @return bool
     */
    private function isExcluded(): bool
    {
        return in_array($this->getProductType(), $this->config->getExcludedProductTypes(), true);
    }

    /**
     * Return config property
     *
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Return widgetConfigHelper for template
     *
     * @return Config
     */
    public function getWidgetConfig(): Config
    {
        return $this->widgetConfigHelper;
    }

    /**
     * Get the current active mode
     *
     * @return string
     */
    public function getActiveMode(): string
    {
        return strtoupper($this->apiConfigHelper->getActiveMode());
    }

    /**
     * Convert to html
     *
     * @return string
     */
    public function _toHtml(): string
    {
        return ($this->getNameInLayout() == $this->widgetConfigHelper->getWidgetPosition()
        && !$this->isExcluded() ? parent::_toHtml() : '');
    }

    /**
     * Return plan in Json for Front treatment
     *
     * @return string
     */
    public function getJsonPlans(): string
    {
        $plans = $this->getPlans();
        return (!empty($plans) ? json_encode($plans) : '');
    }

    /**
     * Return product ID
     *
     * @return int
     */
    public function getProductId(): int
    {
        return $this->getProduct()->getId();
    }

    /**
     * Return product name
     *
     * @return string
     */
    public function getProductName(): string
    {
        return $this->getProduct()->getName();
    }

    /**
     * Return product type for exclusion
     *
     * @return string
     */
    public function getProductType(): string
    {
        return $this->getProduct()->getTypeId();
    }

    /**
     * Return product price for the badge
     *
     * @return int | null
     */
    public function getPrice(): ?int
    {
        return Functions::priceToCents($this->getProduct()->getPriceInfo()->getPrice('final_price')->getAmount()->getValue());
    }

    /**
     * Return locale and convert it
     *
     * @return string
     */
    public function getLocale(): string
    {
        $locale = 'en';
        $localeStoreCode = $this->localeResolver->getLocale();
        if (preg_match('/^([a-z]{2})_([A-Z]{2})$/', $localeStoreCode, $matches)) {
            $locale = $matches[1];
        }
        return $locale;
    }
}
