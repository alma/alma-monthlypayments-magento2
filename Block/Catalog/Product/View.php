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
use Alma\MonthlyPayments\Helpers\Functions;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\Store;

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
     * @var Functions
     */
    private $functions;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Resolver
     */
    private $locale;

    /**
     * @var array
     */
    private $plans = array();

    /**
     * View constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Config $config
     * @param Functions $functions
     * @param Logger $logger
     * @param Resolver $locale
     * @param array $data
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Config $config,
        Functions $functions,
        Logger $logger,
        Resolver $locale,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->registry = $registry;
        $this->functions = $functions;
        $this->logger = $logger;
        $this->locale = $locale;
        $this->getProduct();
        $this->getPlans();
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
            if( $this->isEnabledBadge($planConfig->installmentsCount()) ){
                $this->plans[] = array(
                    'installmentsCount' => $planConfig->installmentsCount(),
                    'minAmount' => $planConfig->minimumAmount(),
                    'maxAmount' => $planConfig->maximumAmount()
                );
            }
        }
    }

    /**
     * @param int
     * @return bool
     */
    private function isEnabledBadge($installments_count)
    {
        return in_array($installments_count, array(1,2,3,4,10,12));
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
     * @return string
     */
    public function getActiveMode()
    {
        return strtoupper($this->config->getActiveMode());
    }

    /**
     * @return string
     */
    public function _toHtml()
    {
        return ($this->getNameInLayout() == $this->config->getWidgetPosition()
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
        return $this->functions->priceToCents($this->product->getFinalPrice());
    }
    /**
     * Return locale and convert it
     * @return string
     */
    public function getLocale(){
        $locale ='en';
        $localeStoreCode = $this->locale->getLocale();

        if (preg_match('/^([a-z]{2})_([A-Z]{2})$/',$localeStoreCode,$matches)){
            $locale = $matches[1];
        }
        return $locale;
    }
}
