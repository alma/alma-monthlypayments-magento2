<?php

namespace Alma\MonthlyPayments\Gateway\Config\PaymentPlans;

use Alma\API\Entities\FeePlan;

interface PaymentPlanConfigInterface
{
    /**
     * Get all the keys used for storing payment plan data in transients
     *
     * @return array
     */
    public function transientKeys(): array;

    /**
     * Get plan key
     *
     * @param FeePlan $plan
     * @return string
     */
    public function keyForFeePlan(FeePlan $plan): string;

    /**
     * Get default config for a fee plan
     *
     * @param FeePlan $plan
     * @return array
     */
    public function defaultConfigForFeePlan(FeePlan $plan): array;

    /**
     * Return the config for a fee plan in array format
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Get plan key
     *
     * @return string
     */
    public function planKey(): string;

    /**
     * Return plan key alma format
     *
     * @return string
     */
    public function almaPlanKey(): string;

    /**
     * Get payment Data
     *
     * @return array
     */
    public function getPaymentData(): array;

    /**
     * Get King ( general )
     *
     * @return string
     */
    public function kind(): string;

    /**
     * Plan is allowed
     *
     * @return bool
     */
    public function isAllowed(): bool;

    /**
     * Plan is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Get installments count
     *
     * @return int
     */
    public function installmentsCount(): int;

    /**
     * Plan is deferred payment
     *
     * @return bool
     */
    public function isDeferred(): bool;

    /**
     * Return deferred type M or D
     *
     * @return string|null
     */
    public function deferredType(): ?string;

    /**
     * Return deferred days count
     *
     * @return int
     */
    public function deferredDays(): int;

    /**
     * Return deferred months count
     *
     * @return int
     */
    public function deferredMonths(): int;

    /**
     * Plan has deferred payment trigger
     *
     * @return bool
     */
    public function hasDeferredTrigger(): bool;

    /**
     * Get max deferred trigger days count
     *
     * @return int
     */
    public function maxDeferredTriggerDays(): int;

    /**
     * Returns deferred duration in days – approximate value (invariably using 30 days for 1 month) but it's OK as it's
     *
     * @return int
     */
    public function deferredDurationInDays(): int;

    /**
     * Get deferred duration in month or in day
     *
     * @return int
     */
    public function deferredDuration(): int;

    /**
     * Get plan min amount
     *
     * @return int
     */
    public function minimumAmount(): int;

    /**
     * Set plan min amount
     *
     * @param int $amount
     * @return mixed
     */
    public function setMinimumAmount(int $amount);

    /**
     * Get plan min amount allowed
     *
     * @return int
     */
    public function minimumAllowedAmount(): int;

    /**
     * Get plan max amount
     *
     * @return int
     */
    public function maximumAmount(): int;

    /**
     * Set plan max amount
     *
     * @param int $amount
     * @return mixed
     */
    public function setMaximumAmount(int $amount): void;

    /**
     * Get plan max amount allowed
     *
     * @return int
     */
    public function maximumAllowedAmount(): int;

    /**
     * Get variable merchant fees
     *
     * @return int
     */
    public function variableMerchantFees(): int;

    /**
     * Get fixed merchant fees
     *
     * @return int
     */
    public function fixedMerchantFees(): int;

    /**
     * Get variable customer fees
     *
     * @return int
     */
    public function variableCustomerFees(): int;

    /**
     * Get fixed customer fees
     *
     * @return int
     */
    public function fixedCustomerFees(): int;

    /**
     * Get logo file name
     *
     * @return string|null
     */
    public function logoFileName(): ?string;

    /**
     * Get customer lending rate
     *
     * @return int
     */
    public function customerLendingRate(): int;
}
