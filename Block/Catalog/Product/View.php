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

use Magento\Catalog\Block\Product\Context;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Alma\MonthlyPayments\Helpers\Functions;
use Magento\Framework\Locale\Resolver;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\WidgetConfigHelper;

class View extends Template
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var Resolver
     */
    private $localeResolver;

    /**
     * @var array
     */
    private $plans = array();
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;
    /**
     * @var WidgetConfigHelper
     */
    private $widgetConfigHelper;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ApiConfigHelper $apiConfigHelper
     * @param WidgetConfigHelper $widgetConfigHelper
     * @param Config $config
     * @param Resolver $localeResolver
     * @param array $data
     *
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ApiConfigHelper $apiConfigHelper,
        WidgetConfigHelper $widgetConfigHelper,
        Config $config,
        Resolver $localeResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->registry = $registry;
        $this->localeResolver = $localeResolver;
        $this->getProduct();
        $this->getPlans();
        $this->apiConfigHelper = $apiConfigHelper;
        $this->widgetConfigHelper = $widgetConfigHelper;
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    private function getProduct()
    {
        $this->product = $this->registry->registry('product');
        if (!$this->product->getId()) {
            throw new LocalizedException(__('Failed to initialize product'));
        }
    }

    /**
     * @return void
     */
    private function getPlans()
    {
        foreach ($this->config->getPaymentPlansConfig()->getEnabledPlans() as $planConfig) {
            $this->plans[] = array(
                'installmentsCount' => $planConfig->installmentsCount(),
                'minAmount' => $planConfig->minimumAmount(),
                'maxAmount' => $planConfig->maximumAmount(),
                'deferredDays' => $planConfig->deferredDays(),
                'deferredMonths' => $planConfig->deferredMonths()
            );
        }
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    private function isExcluded()
    {
        return in_array($this->getProductType(), $this->config->getExcludedProductTypes(), true);
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return Config
     */
    public function getWidgetConfig()
    {
        return $this->widgetConfigHelper;
    }

    /**
     * @return string
     */
    public function getActiveMode()
    {
        return strtoupper($this->apiConfigHelper->getActiveMode());
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function _toHtml()
    {
        return ($this->getNameInLayout() == $this->widgetConfigHelper->getWidgetPosition()
        && !$this->isExcluded() ? parent::_toHtml() : '');
    }

    /**
     * @return string
     */
    public function getJsonPlans()
    {
        return (!empty($this->plans) ? json_encode($this->plans) : '');
    }

    /**
     * @return int
     * @throws LocalizedException
     */
    public function getProductId()
    {
        return $this->product->getId();
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getProductName()
    {
        return $this->product->getName();
    }

    /**
     * @return array|string
     * @throws LocalizedException
     */
    public function getProductType()
    {
        return $this->product->getTypeId();
    }

    /**
     * @return int
     * @throws LocalizedException
     */
    public function getPrice()
    {
        return Functions::priceToCents($this->product->getFinalPrice());
    }
    /**
     * Return locale and convert it
     * @return string
     */
    public function getLocale(){
        $locale ='en';
        $localeStoreCode = $this->localeResolver->getLocale();

        if (preg_match('/^([a-z]{2})_([A-Z]{2})$/',$localeStoreCode,$matches)){
            $locale = $matches[1];
        }
        return $locale;
    }
}
