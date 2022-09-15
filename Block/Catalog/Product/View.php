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
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Exceptions\AlmaProductException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Alma\MonthlyPayments\Helpers\Functions;
use Magento\Framework\Locale\Resolver;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\WidgetConfigHelper;
use Magento\Framework\View\Element\Template\Context;

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
     * @var Session
     */
    private $catalogSession;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ApiConfigHelper $apiConfigHelper
     * @param WidgetConfigHelper $widgetConfigHelper
     * @param Config $config
     * @param Resolver $localeResolver
     * @param Session $catalogSession
     * @param Logger $logger
     * @param ProductRepository $productRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ApiConfigHelper $apiConfigHelper,
        WidgetConfigHelper $widgetConfigHelper,
        Config $config,
        Resolver $localeResolver,
        Session $catalogSession,
        Logger $logger,
        ProductRepository $productRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->logger = $logger;
        $this->catalogSession = $catalogSession;
        $this->config = $config;
        $this->registry = $registry;
        $this->localeResolver = $localeResolver;
        $this->apiConfigHelper = $apiConfigHelper;
        $this->widgetConfigHelper = $widgetConfigHelper;
        $this->productRepository = $productRepository;
        $this->getPlans();
    }

    /**
     * Get last viewed product id session
     * @return int
     */
    private function getLastViewProductId(): int
    {
        $this->logger->info('$this->catalogSession->getData()', [$this->catalogSession->getData()]);
        return intval($this->catalogSession->getData('last_viewed_product_id'));
    }

    /**
     * @return ProductInterface
     * @throws AlmaProductException
     */
    private function getProduct(): ProductInterface
    {
        if (!is_null($this->product)) {
            return $this->product;
        }

        try {
            $this->product = $this->productRepository->getById($this->getLastViewProductId());
        } catch (NoSuchEntityException $e) {
            $this->logger->error('No product in product Repository with this id', ['Exception message' => $e->getMessage(), 'Product ID' => $this->getLastViewProductId()]);
            throw new AlmaProductException(sprintf('No product in product Repository with this id %s', $this->getLastViewProductId()));
        }

        if (is_null($this->product)) {
            $this->product = $this->registry->registry('product');
            $this->logger->info('with registry', [$this->product]);
        }
        if (is_null($this->product)) {
            $this->logger->error('Impossible to get product with registry method', []);
            throw new AlmaProductException('Error in getProduct in magento product repository');
        }
        return $this->product;
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
     */
    private function isExcluded(): bool
    {
        return in_array($this->getProductType(), $this->config->getExcludedProductTypes(), true);
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return Config
     */
    public function getWidgetConfig(): Config
    {
        return $this->widgetConfigHelper;
    }

    /**
     * @return string
     */
    public function getActiveMode(): string
    {
        return strtoupper($this->apiConfigHelper->getActiveMode());
    }

    /**
     * @return string
     */
    public function _toHtml(): string
    {
        return ($this->getNameInLayout() == $this->widgetConfigHelper->getWidgetPosition()
        && !$this->isExcluded() ? parent::_toHtml() : '');
    }

    /**
     * @return string
     */
    public function getJsonPlans(): string
    {
        return (!empty($this->plans) ? json_encode($this->plans) : '');
    }

    /**
     * @return int | null
     */
    public function getProductId(): ?int
    {
        try {
            return $this->getProduct()->getId();
        } catch (AlmaProductException $e) {
            return null;
        }
    }

    /**
     * @return string | null
     */
    public function getProductName(): ?string
    {
        try {
            return $this->getProduct()->getName();
        } catch (AlmaProductException $e) {
            return null;
        }
    }

    /**
     * @return string | null
     */
    public function getProductType(): ?string
    {
        try {
            return $this->getProduct()->getTypeId();
        } catch (AlmaProductException $e) {
            return null;
        }
    }

    /**
     * @return int | null
     */
    public function getPrice(): ?int
    {
        try {
            return Functions::priceToCents($this->getProduct()->getFinalPrice());
        } catch (AlmaProductException $e) {
            return null;
        }
    }
    /**
     * Return locale and convert it
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
