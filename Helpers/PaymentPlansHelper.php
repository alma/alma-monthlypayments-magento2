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


namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Entities\FeePlan;
use Alma\API\RequestError;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlanConfig;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterface;
use Magento\Framework\Message\Manager as MessageManager;

class PaymentPlansHelper
{
    const TRANSIENT_KEY_MIN_ALLOWED_AMOUNT = 'minAllowedAmount';
    const KEY_MIN_AMOUNT = 'minAmount';
    const TRANSIENT_KEY_MAX_ALLOWED_AMOUNT = 'maxAllowedAmount';
    const KEY_MAX_AMOUNT = 'maxAmount';

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var PaymentPlansConfigInterface
     */
    private $paymentPlansConfig;
    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @param Logger $logger
     * @param PaymentPlansConfigInterface $paymentPlansConfig
     * @param MessageManager $messageManager
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Logger $logger,
        PaymentPlansConfigInterface $paymentPlansConfig,
        MessageManager $messageManager,
        ConfigHelper $configHelper
    ) {
        $this->logger = $logger;
        $this->configHelper = $configHelper;
        $this->paymentPlansConfig = $paymentPlansConfig;
        $this->messageManager = $messageManager;
    }

    /**
     * @return bool
     */
    public function paymentTriggerIsAllowed(): bool
    {
        $triggerIsAllowed = false;

        $feePlans = $this->configHelper->getBaseApiPlansConfig();

        foreach ($feePlans as $plan) {
            $deferredLimitDays  = $plan->getDeferredTriggerLimitDays();
            if (isset($deferredLimitDays)) {
                $triggerIsAllowed = true;
                break;
            }
        }
        return $triggerIsAllowed;
    }

    /**
     * @return void
     */
    public function saveBaseApiPlansConfig(): void
    {
        try {
            $apiPlans = $this->paymentPlansConfig->getFeePlansFromApi();
            $baseFeePlans = [];
            foreach ($apiPlans as $feePlan) {
                $planKey = PaymentPlanConfig::keyForFeePlan($feePlan);
                $baseFeePlans[$planKey] = $feePlan;
            }
            $this->configHelper->saveBasePlansConfig($baseFeePlans);
        } catch (RequestError $e) {
            $this->logger->error('Error in save api base config plans', [$e->getMessage()]);
        }
    }

    /**
     * @param $plan
     *
     * @return array
     */
    private function forceAmountThresholds($plan): array
    {
        $key = $plan['kind'].':'.$plan['installmentsCount'].':'.$plan['deferredDays'].':'.$plan['deferredMonths'];
        if (
            $plan[self::KEY_MIN_AMOUNT] < $plan[self::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT] ||
            $plan[self::KEY_MIN_AMOUNT] > $plan[self::TRANSIENT_KEY_MAX_ALLOWED_AMOUNT] ||
            $plan[self::KEY_MIN_AMOUNT] > $plan[self::KEY_MAX_AMOUNT]
        ) {
            $plan[self::KEY_MIN_AMOUNT] = $plan[self::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT];
            $this->messageManager->addErrorMessage(
                sprintf(__("Minimum amount is %s€ for plan %s"), ($plan[PaymentPlanConfig::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT] / 100), $this->planLabelByKey($key))
            );
        }
        if (
            $plan[self::KEY_MAX_AMOUNT] > $plan[self::TRANSIENT_KEY_MAX_ALLOWED_AMOUNT] ||
            $plan[self::KEY_MAX_AMOUNT] < $plan[self::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT] ||
            $plan[self::KEY_MAX_AMOUNT] < $plan[self::KEY_MIN_AMOUNT]
        ) {
            $plan[self::KEY_MAX_AMOUNT] = $plan[self::TRANSIENT_KEY_MAX_ALLOWED_AMOUNT];
            $this->messageManager->addErrorMessage(
                sprintf(__("Maximum amount is %s€ for plan %s"), ($plan[PaymentPlanConfig::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT] / 100), $this->planLabelByKey($key))
            );
        }
        return $plan;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function planLabelByKey(string $key): string
    {
        preg_match('!general:([\d]+):([\d]+):([\d]+)!', $key, $matches);
        $label = $key;

        if (isset($matches[1])) {
            $label =  __('Pay in %1 installments', $matches[1]);
        }
        if (isset($matches[2]) && $matches[2] != 0) {
            $label =  __('Pay later - D+%1', $matches[2]);
        }
        return $label;
    }

    public function formatFeePlanConfigForBackOfficeDisplaying(FeePlan $feePlan, array $feePlanConfig = null): array
    {
        $key = PaymentPlanConfig::keyForFeePlan($feePlan);
        $defaultEnabled = 0;
        if ($key === 'general:3:0:0') {
            $defaultEnabled = 1;
        }
        return [
            'key' => $key,
            'pnx_label' => $this->planLabelByKey($key),
            'enabled' => isset($feePlanConfig['enabled']) ? intval($feePlanConfig['enabled']) : $defaultEnabled,
            'min_purchase_amount' => Functions::priceFromCents($feePlan->min_purchase_amount),
            'custom_min_purchase_amount' => isset($feePlanConfig['minAmount']) ? Functions::priceFromCents(intval($feePlanConfig['minAmount'])) : Functions::priceFromCents($feePlan->min_purchase_amount),
            'custom_max_purchase_amount' => isset($feePlanConfig['maxAmount']) ? Functions::priceFromCents(intval($feePlanConfig['maxAmount'])) : Functions::priceFromCents($feePlan->max_purchase_amount),
            'max_purchase_amount' => Functions::priceFromCents($feePlan->max_purchase_amount),
            'fee' => $this->getFee($feePlan)
        ];
    }

    /**
     * @param FeePlan $feePlan
     *
     * @return array
     */
    private function getFee(FeePlan $feePlan): array
    {
        $fee = [];
        $fee['merchant'] = ['merchant_fee_fixed' => $feePlan->merchant_fee_fixed, 'merchant_fee_variable' => $feePlan->merchant_fee_variable ];
        $fee['customer'] = ['customer_fee_fixed' => $feePlan->customer_fee_fixed, 'customer_fee_variable' => $feePlan->customer_fee_variable ];
        return $fee;
    }

    /**
     * @param FeePlan $feePlan
     * @param array $configInput
     *
     * @return array
     */
    public function formatFeePlanConfigForSave(FeePlan $feePlan, array $configInput): array
    {
        $newFeePlan = PaymentPlanConfig::defaultConfigForFeePlan($feePlan);
        $newFeePlan['enabled'] = $configInput['enabled'];
        $newFeePlan['minAmount'] = Functions::priceToCents($configInput['custom_min_purchase_amount']);
        $newFeePlan['maxAmount'] = Functions::priceToCents($configInput['custom_max_purchase_amount']);
        return $this->forceAmountThresholds($newFeePlan);
    }
}
