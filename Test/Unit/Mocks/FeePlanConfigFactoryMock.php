<?php

namespace Alma\MonthlyPayments\Test\Unit\Mocks;

class FeePlanConfigFactoryMock
{
    public static function feePlanConfigFactory($key, $enabled = 0, $triggerDays = null, $minAMount = 5000, $MaxAmount = 200000, $minAllowedAMount = 5000, $maxAllowedAMount = 200000): array
    {
        preg_match('!general:([\d]+):([\d]+):([\d]+)!', $key, $matches);
        return [
            'kind' => 'general',
            'installmentsCount' => $matches[1],

            'deferredDays' => intval($matches[2]),
            'deferredMonths' => intval($matches[3]),

            'deferredTriggerEnable' => !empty($triggerDays),
            'deferredTriggerDays' => intval($triggerDays),

            'enabled' => $enabled,

            'minAllowedAmount' => $minAllowedAMount,
            'minAmount' => $minAMount,

            'maxAllowedAmount' => $maxAllowedAMount,
            'maxAmount' => $MaxAmount,

            'customerLendingRate' => 0,

            'merchantFees' => [
                'variable' => 75,
                'fixed' => 0
            ],
            'customerFees' => [
                'variable' => 0,
                'fixed' => 0
            ]
        ];
    }

    public static function getDefaultDataPlan(): array
    {
        return [
            'key' => FeePlanFactoryMock::KEY_2,
            'pnx_label' => 'Pay in 2 installments',
            'enabled' => 0,
            'min_purchase_amount' => 50.0,
            'custom_min_purchase_amount' => 50.0,
            'custom_max_purchase_amount' => 2000.0,
            'max_purchase_amount' => 2000.0,
        ];
    }

    public static function feePlanConfigForDisplayFactory($data): array
    {
        return [
            "key" => $data['key'],
            "pnx_label" => $data['pnx_label'],
            "enabled" => $data['enabled'],
            "min_purchase_amount" => $data['min_purchase_amount'],
            "custom_min_purchase_amount" => $data['custom_min_purchase_amount'],
            "custom_max_purchase_amount" => $data['custom_max_purchase_amount'],
            "max_purchase_amount" => $data['max_purchase_amount']
        ];
    }
}
