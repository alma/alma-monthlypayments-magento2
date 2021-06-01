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
use Alma\MonthlyPayments\Helpers;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

class View extends Template
{
    /**
     * @var Helpers\Eligibility
     */
    private $eligibilityHelper;

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
     * @var array
     */
    private $plans = array();

    private const WIDGET_POSITION = 'payment/alma_monthly_payments/widget_position';

    private const EXCLUDED_PRODUCT_TYPES = 'payment/alma_monthly_payments/excluded_product_types';

    public $widgetContainer;

    /**
     * View constructor.
     * @param Context $context
     * @param Helpers\Eligibility $eligibilityHelper
     * @param Registry $registry
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Helpers\Eligibility $eligibilityHelper,
        Registry $registry,
        Config $config,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->eligibilityHelper = $eligibilityHelper;
        $this->config = $config;
        $this->registry = $registry;
    }

    /**
     * @return string
     */
    public function _toHtml()
    {
        return ($this->getNameInLayout() == $this->getConfig(SELF::WIDGET_POSITION)
        && !$this->isExcluded() ? parent::_toHtml() : '');
    }

    public function isExcluded()
    {
        return in_array($this->getProductType(), $this->getExcludedProductType(), true);
    }

    /**
     * Get config value.
     *
     * @param string $path
     * @return string|null
     */
    public function getConfig($path)
    {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return false|string
     */
    public function getPlans()
    {
        if (empty($this->plans)) {
            foreach ($this->config->getPaymentPlansConfig()->getEnabledPlans() as $planConfig) {
                $this->plans[] = array(
                    'installmentsCount' => $planConfig->installmentsCount(),
                    'minAmount' => $planConfig->minimumAmount(),
                    'maxAmount' => $planConfig->maximumAmount()
                );
            }
        }
        return (!empty($this->plans) ? json_encode($this->plans) : false);
    }

    public function getExcludedProductType()
    {
        return explode(',', $this->getConfig(SELF::EXCLUDED_PRODUCT_TYPES));
    }

    /**
     * @return Product|mixed|null
     * @throws LocalizedException
     */
    private function getProduct()
    {
        if (is_null($this->product)) {
            $this->product = $this->registry->registry('product');
            if (!$this->product->getId()) {
                throw new LocalizedException(__('Failed to initialize product'));
            }
        }
        return $this->product;
    }

    /**
     * @return int
     * @throws LocalizedException
     */
    public function getProductId()
    {
        return $this->getProduct()->getId();
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getProductName()
    {
        return $this->getProduct()->getName();
    }

    /**
     * @return array|string
     * @throws LocalizedException
     */
    public function getProductType()
    {
        return $this->getProduct()->getTypeId();
    }

    /**
     * @return float|int
     * @throws LocalizedException
     */
    public function getPrice()
    {
        return $this->getProduct()->getFinalPrice() * 100;
    }

    /**
     * @return string|null
     */
    public function isActive()
    {
        return $this->getConfig('payment/alma_monthly_payments/widget_active');
    }

    /**
     * @return string|null
     */
    public function getAlmaWidgetContainer()
    {
        if (!$this->widgetContainer) {
            $this->widgetContainer =
                $this->getConfig('payment/alma_monthly_payments/widget_container_jquery_selector');
        }
        return $this->widgetContainer;
    }

    /**
     * @return string
     */
    public function getAlmaApiMode()
    {
        return strtoupper($this->getConfig('payment/alma_monthly_payments/api_mode'));
    }

    /**
     * @return string|null
     */
    public function getAlmaMerchantId()
    {
        return $this->getConfig('payment/alma_monthly_payments/merchant_id');
    }

    /**
     * @return string
     */
    public function getAlmaIsDynamicQtyPrice()
    {
        return ($this->getConfig('payment/alma_monthly_payments/widget_price_per_qty') ? 'true' : 'false');
    }

    /**
     * @return string
     */
    public function getAlmaIsPrepend()
    {
        return ($this->getConfig('payment/alma_monthly_payments/widget_container_prepend') ? 'true' : 'false');
    }

    /**
     * @return string
     */
    public function isCustomWidgetPosition()
    {
        return ($this->getConfig('payment/alma_monthly_payments/widget_position') ==
        'catalog.product.view.custom.alma.widget' ? 'true' : 'false');
    }
}
