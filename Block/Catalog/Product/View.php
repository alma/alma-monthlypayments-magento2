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

    private const ALMA_API_MODE = 'payment/alma_monthly_payments/api_mode';
    private const ALMA_MERCHANT_ID = 'payment/alma_monthly_payments/merchant_id';
    private const WIDGET_POSITION = 'payment/alma_monthly_payments/widget_position';
    private const WIDGET_ACTIVE = 'payment/alma_monthly_payments/widget_active';
    private const WIDGET_CONTAINER = 'payment/alma_monthly_payments/widget_container_css_selector';
    private const WIDGET_PRICE_PER_QTY = 'payment/alma_monthly_payments/widget_price_per_qty';
    private const EXCLUDED_PRODUCT_TYPES = 'payment/alma_monthly_payments/excluded_product_types';
    private const WIDGET_CONTAINER_PREPEND = 'payment/alma_monthly_payments/widget_container_prepend';

    /**
     * @var string
     */
    public $widgetContainer;

    /**
     * View constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Config $config
     * @param array $data
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Config $config,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->registry = $registry;
        $this->_initProduct();
        $this->_initPlans();
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    private function _initProduct()
    {
        $this->product = $this->registry->registry('product');
        if (!$this->product->getId()) {
            throw new LocalizedException(__('Failed to initialize product'));
        }
    }

    /**
     * @return void
     */
    private function _initPlans()
    {
        foreach ($this->config->getPaymentPlansConfig()->getEnabledPlans() as $planConfig) {
            $this->plans[] = array(
                'installmentsCount' => $planConfig->installmentsCount(),
                'minAmount' => $planConfig->minimumAmount(),
                'maxAmount' => $planConfig->maximumAmount()
            );
        }
    }

    /**
     * Get config value.
     *
     * @param string $path
     * @return mixed
     */
    private function getConfig($path)
    {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    private function isExcluded()
    {
        return in_array($this->getProductType(), $this->getExcludedProductType(), true);
    }

    /**
     * @return string[]|false
     */
    private function getExcludedProductType()
    {
        return explode(',', $this->getConfig(SELF::EXCLUDED_PRODUCT_TYPES));
    }

    /**
     * @return string
     */
    public function _toHtml()
    {
        return ($this->getNameInLayout() == $this->getConfig(SELF::WIDGET_POSITION)
        && !$this->isExcluded() ? parent::_toHtml() : '');
    }

    /**
     * @return string
     */
    public function getJsonPlans(){
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
        return $this->product->getFinalPrice() * 100;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->getConfig(SELF::WIDGET_ACTIVE);
    }

    /**
     * @return string
     */
    public function getAlmaWidgetContainer()
    {
        if (!$this->widgetContainer) {
            $this->widgetContainer =
                $this->getConfig(SELF::WIDGET_CONTAINER);
        }
        return $this->widgetContainer;
    }

    /**
     * @return string
     */
    public function getAlmaApiMode()
    {
        return strtoupper($this->getConfig(SELF::ALMA_API_MODE));
    }

    /**
     * @return string
     */
    public function getAlmaMerchantId()
    {
        return $this->getConfig(SELF::ALMA_MERCHANT_ID);
    }

    /**
     * @return string used by javascript in view.phtml
     */
    public function getAlmaIsDynamicQtyPrice()
    {
        return ($this->getConfig(SELF::WIDGET_PRICE_PER_QTY) ? 'true' : 'false');
    }

    /**
     * @return string used by javascript in view.phtml
     */
    public function getAlmaIsPrepend()
    {
        return ($this->getConfig(SELF::WIDGET_CONTAINER_PREPEND) ? 'true' : 'false');
    }

    /**
     * @return string used by javascript in view.phtml
     */
    public function isCustomWidgetPosition()
    {
        return ($this->getConfig(SELF::WIDGET_POSITION) ==
        'catalog.product.view.custom.alma.widget' ? 'true' : 'false');
    }
}
