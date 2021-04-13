<?php
/**
 * 2018-2021 Alma SAS
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
 * @author    Alma SAS <contact@getalma.eu>
 * @copyright 2018-2021 Alma SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */


namespace Alma\MonthlyPayments\Model\Adminhtml\Config;

use Alma\API\Entities\FeePlan;
use Alma\API\RequestError;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlanConfig;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfig;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Magento\Config\Model\Config\Backend\Serialized;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\Manager as MessageManager;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;

class PaymentPlans extends Serialized
{
    protected $apiKeyType = null;

    /**
     * @var AlmaClient
     */
    private $almaClient;
    /**
     * @var MessageManager
     */
    private $messageManager;
    /**
     * @var false
     */
    protected $hasError;
    /**
     * @var Config
     */
    private $almaConfig;
    /**
     * @var Json
     */
    protected $serializer;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AlmaClient $almaClient,
        MessageManager $messageManager,
        Config $almaConfig,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        Json $serializer = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data,
            $serializer
        );

        $this->almaClient = $almaClient;
        $this->almaConfig = $almaConfig;
        $this->messageManager = $messageManager;
        $this->serializer = $serializer ?: new Json();
    }

    private function defaultConfigForPlan(FeePlan $plan): array
    {
        return [
            'kind' => $plan->kind,

            'installmentsCount' => $plan->installments_count,

            'deferredDays' => intval($plan->deferred_days),
            'deferredMonths' => intval($plan->deferred_months),

            'enabled' => $plan->installments_count === 3,

            'minAllowedAmount' => $plan->min_purchase_amount,
            'minAmount' => $plan->min_purchase_amount,

            'maxAllowedAmount' => $plan->max_purchase_amount,
            'maxAmount' => $plan->max_purchase_amount,

            'merchantFees' => [
                'variable' => $plan->merchant_fee_variable,
                'fixed' => $plan->merchant_fee_fixed
            ],
            'customerFees' => [
                'variable' => $plan->customer_fee_variable,
                'fixed' => $plan->customer_fee_fixed
            ]
        ];
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();

        $value = $this->getValue();
        if ($value === false) {
            $value = [];
        }

        $plansConfig = new PaymentPlansConfig($value);

        $alma = $this->almaClient->getDefaultClient();
        try {
            // TODO: Request deferred plans when support for Pay Later is added
            $feePlans = $alma->merchants->feePlans(FeePlan::KIND_GENERAL, "all", false);
        } catch (RequestError $e) {
            $this->messageManager->addErrorMessage(
                __("Error fetching Alma payment plans - displayed information might not be accurate")
            );

            return;
        }

        foreach ($feePlans as $plan) {
            $key = PaymentPlanConfig::keyForFeePlan($plan);
            $plansConfig->setPlanAllowed($key, $plan->allowed);
            $plansConfig->updatePlanDefaults($key, $this->defaultConfigForPlan($plan));
        }

        $this->setValue($plansConfig);
    }

    public function beforeSave()
    {
        $value = $this->getValue();

        if (!is_array($value)) {
            $value = $this->serializer->unserialize($value);
        }

        // Remove transient values from the serialized data: it should always come fresh from the API
        foreach (PaymentPlanConfig::transientKeys() as $key) {
            foreach ($value as $planKey => &$planConfig) {
                unset($planConfig[$key]);
            }
        }

        $this->setValue($value);

        return parent::beforeSave();
    }
}
