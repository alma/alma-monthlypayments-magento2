<?php


namespace Alma\MonthlyPayments\Gateway\Config\PaymentPlans;


use Alma\API\Entities\FeePlan;

interface PaymentPlanConfigInterface
{
    /**
     * @return array
     */
    public static function transientKeys(): array;

    /**
     * @param FeePlan $plan
     * @return string
     */
    public static function keyForFeePlan(FeePlan $plan): string;

    /**
     * @param FeePlan $plan
     * @return array
     */
    public static function defaultConfigForFeePlan(FeePlan $plan): array;

    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @return string
     */
    public function planKey(): string;

    /**
     * @return string
     */
    public function almaPlanKey(): string;

    /**
     * @return array
     */
    public function getPaymentData(): array;

    /**
     * @return string
     */
    public function kind(): string;

    /**
     * @return bool
     */
    public function isAllowed(): bool;

    /**
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * @return int
     */
    public function installmentsCount(): int;

    /**
     * @return bool
     */
    public function isDeferred(): bool;

    /**
     * @return string|null
     */
    public function deferredType();

    /**
     * @return int
     */
    public function deferredDays(): int;

    /**
     * @return int
     */
    public function deferredMonths(): int;

    /**
     * @return bool
     */
    public function hasDeferredTrigger(): bool;

    /**
     * @return int
     */
    public function maxDeferredTriggerDays(): int;

    /**
     * Returns deferred duration in days – approximate value (invariably using 30 days for 1 month) but it's OK as it's
     * mainly being used for sorting purposes.
     */
    public function deferredDurationInDays(): int;

    /**
     * @return int
     */
    public function deferredDuration(): int;

    /**
     * @return int
     */
    public function minimumAmount(): int;

    /**
     * @param int $amount
     * @return mixed
     */
    public function setMinimumAmount(int $amount);

    /**
     * @return int
     */
    public function minimumAllowedAmount(): int;

    /**
     * @return int
     */
    public function maximumAmount(): int;

    /**
     * @param int $amount
     * @return mixed
     */
    public function setMaximumAmount(int $amount);

    /**
     * @return int
     */
    public function maximumAllowedAmount(): int;

    /**
     * @return int
     */
    public function variableMerchantFees(): int;

    /**
     * @return int
     */
    public function fixedMerchantFees(): int;

    /**
     * @return int
     */
    public function variableCustomerFees(): int;

    /**
     * @return int
     */
    public function fixedCustomerFees(): int;

    /**
     * @return string|null
     */
    public function logoFileName();

    /**
     * @return int
     */
    public function customerLendingRate(): int;
}
