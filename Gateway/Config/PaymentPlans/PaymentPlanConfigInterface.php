<?php


namespace Alma\MonthlyPayments\Gateway\Config\PaymentPlans;


use Alma\API\Entities\FeePlan;

interface PaymentPlanConfigInterface
{
    public static function transientKeys(): array;

    public static function keyForFeePlan(FeePlan $plan): string;

    public static function defaultConfigForFeePlan(FeePlan $plan): array;

    public function toArray(): array;

    public function planKey(): string;

    public function getPaymentData(): array;

    public function kind(): string;

    public function isAllowed(): bool;

    public function isEnabled(): bool;

    public function installmentsCount(): int;

    public function isDeferred(): bool;

    /**
     * @return string|null
     */
    public function deferredType();

    public function deferredDays(): int;

    public function deferredMonths(): int;

    /**
     * Returns deferred duration in days – approximate value (invariably using 30 days for 1 month) but it's OK as it's
     * mainly being used for sorting purposes.
     */
    public function deferredDurationInDays(): int;

    public function deferredDuration(): int;

    public function minimumAmount(): int;

    public function setMinimumAmount(int $amount);

    public function minimumAllowedAmount(): int;

    public function maximumAmount(): int;

    public function setMaximumAmount(int $amount);

    public function maximumAllowedAmount(): int;

    public function variableMerchantFees(): int;

    public function fixedMerchantFees(): int;

    public function variableCustomerFees(): int;

    public function fixedCustomerFees(): int;

    /**
     * @return string|null
     */
    public function logoFileName();
}
