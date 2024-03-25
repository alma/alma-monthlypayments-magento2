<?php

namespace Alma\MonthlyPayments\Test\Unit\Mocks;

use Alma\API\Entities\FeePlan;

class FeePlanFactoryMock
{
    CONST KEY_2 = 'general:2:0:0';
    CONST KEY_3 = 'general:3:0:0';
    CONST KEY_4 = 'general:4:0:0';
    CONST KEY_15 = 'general:1:15:0';

    public static function feePlanFactory(
        string $key,
        bool $allowed = true,
        string $triggerDays = null,
        int $minAmount = 5000,
        int $maxAmount = 200000
    ): FeePlan {
        $planData = self::dataFeePlanFactory(
            $key,
            $allowed,
            $triggerDays,
            $minAmount,
            $maxAmount
        );

        return new FeePlan($planData);
    }

    public static function dataFeePlanFactory(
        string $key,
        bool $allowed = true,
        string $triggerDays = null,
        int $minAmount = 5000,
        int $maxAmount = 200000
    ): array {
        preg_match('!general:([\d]+):([\d]+):([\d]+)!', $key, $matches);
        return [
            'allowed' => $allowed,
            'available_in_pos' => true,
            'capped' => false,
            'customer_fee_fixed' => 0,
            'customer_fee_variable' => 0,
            'customer_lending_rate' => 0,
            'deferred_days' => $matches[2],
            'deferred_months' => $matches[3],
            'deferred_trigger_bypass_scoring' => false,
            'deferred_trigger_limit_days' => $triggerDays,
            'first_installment_ratio' => null,
            'installments_count' => $matches[1],
            'is_under_maximum_interest_regulated_rate' => true,
            'kind' => 'general',
            'max_purchase_amount' => $maxAmount,
            'merchant' => 'merchant_11tqLqZ6gQgUg6jrkXSrz7rGdIMuI5oImX',
            'merchant_fee_variable' => 75,
            'merchant_fee_fixed' => 0,
            'min_purchase_amount' => $minAmount,
            'payout_on_acceptance' => false
        ];
    }
}
