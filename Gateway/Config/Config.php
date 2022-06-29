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

use Alma\API\RequestError;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterface;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterfaceFactory;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Alma\MonthlyPayments\Helpers\Logger;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const CODE = 'alma_monthly_payments';

    const ORDER_PAYMENT_ID = 'PAYMENT_ID';
    const ORDER_PAYMENT_URL = 'PAYMENT_URL';
    const ORDER_PAYMENT_TRIGGER = 'TRIGGER';
    const CONFIG_SORT_ORDER = 'sort_order';
    const CONFIG_ELIGIBILITY_MESSAGE = 'eligibility_message';
    const CONFIG_NON_ELIGIBILITY_MESSAGE = 'non_eligibility_message';
    const CONFIG_SHOW_ELIGIBILITY_MESSAGE = 'show_eligibility_message';
    const CONFIG_EXCLUDED_PRODUCT_TYPES = 'excluded_product_types';
    const CONFIG_EXCLUDED_PRODUCTS_MESSAGE = 'excluded_products_message';
    const CONFIG_RETURN_URL = 'return_url';
    const CONFIG_IPN_CALLBACK_URL = 'ipn_callback_url';
    const CONFIG_CUSTOMER_CANCEL_URL = 'customer_cancel_url';
    const FAILURE_RETURN_URL = 'failure_return_url';
    const CONFIG_MERCHANT_ID = 'merchant_id';
    const CONFIG_PAYMENT_PLANS = 'payment_plans';

    const ALMA_IS_ACTIVE = 'active';
    const ALMA_API_MODE = 'api_mode';
    const ALMA_MERCHANT_ID = 'merchant_id';

    const EXCLUDED_PRODUCT_TYPES = 'excluded_product_types';


    private $pathPattern;
    private $methodCode;
    private $plansConfigFactory;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param PaymentPlansConfigInterfaceFactory $plansConfigFactory
     * @param ApiConfigHelper $apiConfigHelper
     * @param Logger $logger
     * @param null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        PaymentPlansConfigInterfaceFactory $plansConfigFactory,
        ApiConfigHelper $apiConfigHelper,
        Logger $logger,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    )
    {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->methodCode = $methodCode;
        $this->pathPattern = $pathPattern;
        $this->plansConfigFactory = $plansConfigFactory;
        $this->logger = $logger;
        $this->apiConfigHelper = $apiConfigHelper;
    }

     /**
     * @param string $field
     * @return string
     */
    public function getFieldPath(string $field): string
    {
        return sprintf($this->pathPattern, $this->methodCode, $field);
    }

    /**
     * @param $field
     * @param null $default
     * @param null $storeId
     * @return mixed|null
     */
    public function get($field, $default = null, $storeId = null)
    {
        $value = parent::getValue($field, $storeId);
        if ($value === null) {
            $value = $default;
        }
        return $value;
    }

    /**
     * @return bool
     */
    public function getIsActive(): bool
    {
        return (bool)(int)$this->get(self::ALMA_IS_ACTIVE);
    }

    /**
     * @return int
     */
    public function getSortOrder(): int
    {
        return (int)$this->get(self::CONFIG_SORT_ORDER);
    }

    /**
     * @return mixed|null
     */
    public function getEligibilityMessage()
    {
        return $this->get(self::CONFIG_ELIGIBILITY_MESSAGE);
    }

    /**
     * @return mixed|null
     */
    public function getNonEligibilityMessage()
    {
        return $this->get(self::CONFIG_NON_ELIGIBILITY_MESSAGE);
    }

    /**
     * @return bool
     */
    public function showEligibilityMessage(): bool
    {
        return ((bool)(int)$this->get(self::CONFIG_SHOW_ELIGIBILITY_MESSAGE) && $this->getIsActive());
    }

    /**
     * @return false|string[]
     */
    public function getExcludedProductTypes()
    {
        return explode(',', $this->get(self::CONFIG_EXCLUDED_PRODUCT_TYPES));
    }

    /**
     * @return mixed|null
     */
    public function getExcludedProductsMessage()
    {
        return $this->get(self::CONFIG_EXCLUDED_PRODUCTS_MESSAGE);
    }

    /**
     * @return mixed|null
     */
    public function getReturnUrl()
    {
        return $this->get(self::CONFIG_RETURN_URL);
    }

    /**
     * @return mixed|null
     */
    public function getIpnCallbackUrl()
    {
        return $this->get(self::CONFIG_IPN_CALLBACK_URL);
    }

    /**
     * @return mixed|null
     */
    public function getCustomerCancelUrl()
    {
        return $this->get(self::CONFIG_CUSTOMER_CANCEL_URL);
    }

    /**
     * @return mixed|null
     */
    public function getFailureReturnUrl()
    {
        return $this->get(self::FAILURE_RETURN_URL);
    }

    /**
     * @return mixed|null
     */
    public function getMerchantId()
    {
        return $this->get(self::CONFIG_MERCHANT_ID);
    }

    /**
     * @return PaymentPlansConfigInterface
     */
    public function getPaymentPlansConfig(): PaymentPlansConfigInterface
    {
        $data = $this->get(self::CONFIG_PAYMENT_PLANS, []);

        /** @var PaymentPlansConfigInterface $plansConfig */
        $plansConfig = $this->plansConfigFactory->create(["data" => $data]);

        if (empty($data) && $this->apiConfigHelper->isFullyConfigured()) {
            // No plans config data has ever been saved – fetch what we need
            try {
                $plansConfig->updateFromApi();
            } catch (RequestError $e) {
                $this->logger->error('getPaymentPlansConfig Error : ', [$e->getMessage()]);
            }
        }
        return $plansConfig;
    }
}
