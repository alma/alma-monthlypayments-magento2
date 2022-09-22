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

namespace Alma\MonthlyPayments\Gateway\Config;

use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterface;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterfaceFactory;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\StoreHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    public const CODE = 'alma_monthly_payments';
    public const ORDER_PAYMENT_ID = 'PAYMENT_ID';
    public const ORDER_PAYMENT_URL = 'PAYMENT_URL';
    public const ORDER_PAYMENT_TRIGGER = 'TRIGGER';
    private const CONFIG_SORT_ORDER = 'sort_order';
    private const CONFIG_ELIGIBILITY_MESSAGE = 'eligibility_message';
    private const CONFIG_NON_ELIGIBILITY_MESSAGE = 'non_eligibility_message';
    private const CONFIG_SHOW_ELIGIBILITY_MESSAGE = 'show_eligibility_message';
    private const CONFIG_EXCLUDED_PRODUCT_TYPES = 'excluded_product_types';
    private const CONFIG_EXCLUDED_PRODUCTS_MESSAGE = 'excluded_products_message';
    private const CONFIG_RETURN_URL = 'return_url';
    private const CONFIG_IPN_CALLBACK_URL = 'ipn_callback_url';
    public const CONFIG_CUSTOMER_CANCEL_URL = 'customer_cancel_url';
    private const FAILURE_RETURN_URL = 'failure_return_url';
    private const CONFIG_PAYMENT_PLANS = 'payment_plans';
    private const ALMA_IS_ACTIVE = 'active';

    /**
     * @var string
     */
    private $pathPattern;
    /**
     * @var string | null
     */
    private $methodCode;
    /**
     * @var PaymentPlansConfigInterfaceFactory
     */
    private $plansConfigFactory;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;
    /**
     * @var StoreHelper
     */
    private $storeHelper;

    /**
     * Config constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param PaymentPlansConfigInterfaceFactory $plansConfigFactory
     * @param ApiConfigHelper $apiConfigHelper
     * @param StoreHelper $storeHelper
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        PaymentPlansConfigInterfaceFactory $plansConfigFactory,
        ApiConfigHelper $apiConfigHelper,
        StoreHelper $storeHelper,
        string $methodCode = null,
        string $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->methodCode = $methodCode;
        $this->pathPattern = $pathPattern;
        $this->plansConfigFactory = $plansConfigFactory;
        $this->apiConfigHelper = $apiConfigHelper;
        $this->storeHelper = $storeHelper;
    }

    /**
     * Format config path like payment/alma/enabled
     *
     * @param string $field
     *
     * @return string
     */
    public function getFieldPath(string $field): string
    {
        return sprintf($this->pathPattern, $this->methodCode, $field);
    }

    /**
     * Get config value
     *
     * @param string $field
     * @param mixed|null $default
     * @param int|string|null $storeId
     *
     * @return mixed|null
     */
    public function get(string $field, $default = null, $storeId = null)
    {
        if (!$storeId) {
            $storeId = $this->storeHelper->getStoreId();
        }
        $value = parent::getValue($field, $storeId);
        if ($value === null) {
            $value = $default;
        }
        return $value;
    }

    /**
     * Get Alma plugin enabled status
     *
     * @return bool
     */
    public function getIsActive(): bool
    {
        return (bool)(int)$this->get(self::ALMA_IS_ACTIVE);
    }

    /**
     * Get payment method position from config
     *
     * @return int
     */
    public function getSortOrder(): int
    {
        return (int)$this->get(self::CONFIG_SORT_ORDER);
    }

    /**
     * Get Eligibility message from config
     *
     * @return mixed|null
     */
    public function getEligibilityMessage()
    {
        return $this->get(self::CONFIG_ELIGIBILITY_MESSAGE);
    }

    /**
     * Get Non Eligibility message from configuration
     *
     * @return mixed|null
     */
    public function getNonEligibilityMessage()
    {
        return $this->get(self::CONFIG_NON_ELIGIBILITY_MESSAGE);
    }

    /**
     * Get display eligibility message status from configuration
     *
     * @return bool
     */
    public function showEligibilityMessage(): bool
    {
        return (bool)(int)$this->get(self::CONFIG_SHOW_ELIGIBILITY_MESSAGE) && $this->getIsActive();
    }

    /**
     * Get exclude product types from configuration
     *
     * @return false|string[]
     */
    public function getExcludedProductTypes()
    {
        return explode(',', (string)$this->get(self::CONFIG_EXCLUDED_PRODUCT_TYPES));
    }

    /**
     * Get exclude product message from configuration
     *
     * @return mixed|null
     */
    public function getExcludedProductsMessage()
    {
        return $this->get(self::CONFIG_EXCLUDED_PRODUCTS_MESSAGE);
    }

    /**
     * Get return url from configuration
     *
     * @return mixed|null
     */
    public function getReturnUrl()
    {
        return $this->get(self::CONFIG_RETURN_URL);
    }

    /**
     * Get inp url from configuration
     *
     * @return mixed|null
     */
    public function getIpnCallbackUrl()
    {
        return $this->get(self::CONFIG_IPN_CALLBACK_URL);
    }

    /**
     * Get cancel url from configuration
     *
     * @return mixed|null
     */
    public function getCustomerCancelUrl()
    {
        return $this->get(self::CONFIG_CUSTOMER_CANCEL_URL);
    }

    /**
     * Get failure url from configuration
     *
     * @return mixed|null
     */
    public function getFailureReturnUrl()
    {
        return $this->get(self::FAILURE_RETURN_URL);
    }

    /**
     * Get merchant id for the current active mode
     *
     * @param string|null $storeId
     *
     * @return string|null
     */
    public function getMerchantId(?string $storeId = null): ?string
    {
        $merchantIdPath = $this->apiConfigHelper->getActiveMode() . '_merchant_id';
        return $this->get($merchantIdPath, '', $storeId);
    }

    /**
     * Get payment plans configuration from configuration
     *
     * @return PaymentPlansConfigInterface
     */
    public function getPaymentPlansConfig(): PaymentPlansConfigInterface
    {
        $data = $this->get(self::CONFIG_PAYMENT_PLANS, []);

        $plansConfig = $this->plansConfigFactory->create(["data" => $data]);
        if (empty($data)) {
            // No plans config data has ever been saved – fetch what we need
            $plansConfig->updateFromApi();
        }
        return $plansConfig;
    }
}
