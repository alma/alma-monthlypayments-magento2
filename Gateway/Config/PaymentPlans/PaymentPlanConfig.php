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

namespace Alma\MonthlyPayments\Gateway\Config\PaymentPlans;

use Alma\API\Entities\FeePlan;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;

class PaymentPlanConfig implements PaymentPlanConfigInterface
{
    public const TRANSIENT_KEY_MIN_ALLOWED_AMOUNT = 'minAllowedAmount';
    public const TRANSIENT_KEY_MAX_ALLOWED_AMOUNT = 'maxAllowedAmount';
    private const TRANSIENT_KEY_MERCHANT_FEES = 'merchantFees';
    private const TRANSIENT_KEY_CUSTOMER_FEES = 'customerFees';
    private const ALLOWED_MONTHLY_PLANS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

    /**
     * @var array
     */
    private $data;

    /**
     * @var PaymentPlansHelper
     */
    private $paymentPlansHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * PaymentPlanConfig constructor.
     *
     * @param PaymentPlansHelper $paymentPlansHelper
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        PaymentPlansHelper $paymentPlansHelper,
        Logger $logger,
        array $data = []
    ) {
        $this->data = $data;
        $this->paymentPlansHelper = $paymentPlansHelper;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function transientKeys(): array
    {
        return [
            self::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT,
            self::TRANSIENT_KEY_MAX_ALLOWED_AMOUNT,
            self::TRANSIENT_KEY_MERCHANT_FEES,
            self::TRANSIENT_KEY_CUSTOMER_FEES,
        ];
    }

    /**
     * @inheritDoc
     */
    public function keyForFeePlan(FeePlan $plan): string
    {
        return $this->key(
            $plan->kind,
            (int)$plan->installments_count,
            (int)$plan->deferred_days,
            (int)$plan->deferred_months
        );
    }

    /**
     * @inheritDoc
     */
    public function defaultConfigForFeePlan(FeePlan $plan): array
    {
        $deferred_trigger_limit_days = $plan->getDeferredTriggerLimitDays();
        return [
            'allowed' => $plan->allowed,
            'kind' => $plan->kind,

            'installmentsCount' => $plan->installments_count,

            'deferredDays' => (int)$plan->deferred_days,
            'deferredMonths' => (int)$plan->deferred_months,

            'deferredTriggerEnable' => !empty($deferred_trigger_limit_days),
            'deferredTriggerDays' => (int)$deferred_trigger_limit_days,

            'enabled' => 0,

            'minAllowedAmount' => $plan->min_purchase_amount,
            'minAmount' => $plan->min_purchase_amount,

            'maxAllowedAmount' => $plan->max_purchase_amount,
            'maxAmount' => $plan->max_purchase_amount,

            'customerLendingRate' => $plan->customer_lending_rate,

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

    /**
     * Generate key at local format
     *
     * @param string $planKind
     * @param int $installmentsCount
     * @param int $deferredDays
     * @param int $deferredMonths
     * @return string
     */
    private function key(
        string $planKind,
        int $installmentsCount,
        int $deferredDays,
        int $deferredMonths
    ): string {
        return implode(':', [$planKind, $installmentsCount, $deferredDays, $deferredMonths]);
    }

    /**
     * Generate key at alma format
     *
     * @param string $planKind
     * @param int $installmentsCount
     * @param int $deferredDays
     * @param int $deferredMonths
     * @return string
     */
    private function almaKey(
        string $planKind,
        int $installmentsCount,
        int $deferredDays,
        int $deferredMonths
    ): string {
        return implode('_', [$planKind, $installmentsCount, $deferredDays, $deferredMonths]);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $this->data['key']= $this->planKey();
        $this->data['logo']= $this->logoFileName();
        $this->data['inPageAllowed']= $this->paymentPlansHelper->isInPageAllowed($this->planKey());
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function planKey(): string
    {
        return $this->key($this->kind(), $this->installmentsCount(), $this->deferredDays(), $this->deferredMonths());
    }

    /**
     * @inheritDoc
     */
    public function almaPlanKey(): string
    {
        return $this->almaKey(
            $this->kind(),
            $this->installmentsCount(),
            $this->deferredDays(),
            $this->deferredMonths()
        );
    }

    /**
     * @inheritDoc
     */
    public function getPaymentData(): array
    {
        if (!$this->isAllowed() || !$this->isEnabled()) {
            return [];
        }

        $data = [
            'installments_count' => $this->installmentsCount(),
        ];

        if ($this->isDeferred()) {
            $data['deferred_days'] = $this->deferredDays();
            $data['deferred_months'] = $this->deferredMonths();
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function kind(): string
    {
        return $this->data['kind'];
    }

    /**
     * @inheritDoc
     */
    public function isAllowed(): bool
    {
        return $this->data['allowed'];
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->data['enabled'];
    }

    /**
     * @inheritDoc
     */
    public function installmentsCount(): int
    {
        return $this->data['installmentsCount'];
    }

    /**
     * @inheritDoc
     */
    public function isDeferred(): bool
    {
        return $this->deferredDays() > 0 || $this->deferredMonths() > 0;
    }

    /**
     * @inheritDoc
     */
    public function deferredType(): ?string
    {
        if (!$this->isDeferred()) {
            return null;
        }

        return $this->deferredMonths() > 0 ? 'M' : 'D';
    }

    /**
     * @inheritDoc
     */
    public function deferredDays(): int
    {
        return (int)$this->data['deferredDays'];
    }

    /**
     * @inheritDoc
     */
    public function deferredMonths(): int
    {
        return (int)$this->data['deferredMonths'];
    }

    /**
     * @inheritDoc
     */
    public function hasDeferredTrigger(): bool
    {
        return $this->data['deferredTriggerEnable'];
    }

    /**
     * @inheritDoc
     */
    public function maxDeferredTriggerDays(): int
    {
        return (int)$this->data['deferredTriggerDays'];
    }

    /**
     * @inheritDoc
     */
    public function deferredDurationInDays(): int
    {
        return $this->deferredMonths() * 30 + $this->deferredDays();
    }

    /**
     * @inheritDoc
     */
    public function deferredDuration(): int
    {
        return $this->deferredMonths() ?: $this->deferredDays();
    }

    /**
     * @inheritDoc
     */
    public function minimumAmount(): int
    {
        return $this->data['minAmount'];
    }

    /**
     * @inheritDoc
     */
    public function setMinimumAmount(int $amount)
    {
        $this->data['minAmount'] = $amount;
    }

    /**
     * @inheritDoc
     */
    public function minimumAllowedAmount(): int
    {
        return $this->data['minAllowedAmount'];
    }

    /**
     * @inheritDoc
     */
    public function maximumAmount(): int
    {
        return $this->data['maxAmount'];
    }

    /**
     * @inheritDoc
     */
    public function setMaximumAmount(int $amount): void
    {
        $this->data['maxAmount'] = $amount;
    }

    /**
     * @inheritDoc
     */
    public function maximumAllowedAmount(): int
    {
        return $this->data['maxAllowedAmount'];
    }

    /**
     * @inheritDoc
     */
    public function variableMerchantFees(): int
    {
        return $this->data['merchantFees']['variable'];
    }

    /**
     * @inheritDoc
     */
    public function fixedMerchantFees(): int
    {
        return $this->data['merchantFees']['fixed'];
    }

    /**
     * @inheritDoc
     */
    public function variableCustomerFees(): int
    {
        return $this->data['customerFees']['variable'];
    }

    /**
     * @inheritDoc
     */
    public function fixedCustomerFees(): int
    {
        return $this->data['customerFees']['fixed'];
    }

    /**
     * @inheritDoc
     */
    public function logoFileName(): ?string
    {
        if (!$this->isDeferred() && in_array($this->installmentsCount(), self::ALLOWED_MONTHLY_PLANS)) {
            return 'p' . $this->installmentsCount() . 'x_logo.svg';
        }
        if ($this->isDeferred() && $this->deferredType() === 'D' && $this->installmentsCount() === 1) {
            return $this->deferredDays() . 'j_logo.svg';
        }
        if ($this->isDeferred() && $this->deferredType() === 'M' && $this->installmentsCount() === 1) {
            return $this->deferredMonths() . 'm_logo.svg';
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function customerLendingRate(): int
    {
        return $this->data['customerLendingRate'];
    }
}
