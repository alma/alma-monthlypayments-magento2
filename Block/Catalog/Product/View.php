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
     * @var array
     */
    private $plans = array();

    /**
     * View constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Config $config
     * @param Functions $functions
     * @param array $data
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Config $config,
        Functions $functions,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->registry = $registry;
        $this->functions = $functions;
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
            if( in_array($planConfig->installmentsCount(), array(2,3,4,10,12)) ){
                $this->plans[] = array(
                    'installmentsCount' => $planConfig->installmentsCount(),
                    'minAmount' => $planConfig->minimumAmount(),
                    'maxAmount' => $planConfig->maximumAmount()
                );
            }
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
}
