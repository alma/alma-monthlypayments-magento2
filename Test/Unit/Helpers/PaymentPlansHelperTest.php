<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Entities\FeePlan;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterface;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\TestCase;

class PaymentPlansHelperTest extends TestCase
{
    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->paymentPlansConfig = $this->createMock(PaymentPlansConfigInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);
    }
    private function getDependency(): array
    {
        return [
            $this->logger,
            $this->paymentPlansConfig,
            $this->serializer,
            $this->configHelper
        ];
    }
    private function getArrayPlansKey(): array
    {
        return [
            ['key' => 'general:1:0:0', 'allowed' => true, 'trigger' => null ],
            ['key' => 'general:2:0:0', 'allowed' => false, 'trigger' => null ],
            ['key' => 'general:3:0:0', 'allowed' => true, 'trigger' => '30' ]
        ];
    }

    public function testSaveBaseApiPlanConfigWriteWithParams(): void
    {
        $arrayPlansKey = $this->getArrayPlansKey();

        $mockFeePlans = $this->getApiPlansConfigMock($arrayPlansKey);
        $this->paymentPlansConfig->method('getFeePlansFromApi')->willReturn($mockFeePlans);

        $this->configHelper->expects($this->once())->method('saveBasePlansConfig')->with($this->getApiPlansConfigResult($arrayPlansKey));
        $this->createPaymentPlansHelper()->saveBaseApiPlansConfig();
    }

    public function testPaymentTriggerIsAllowedWithOneTrue(): void
    {
        $this->configHelper->method('getBaseApiPlansConfig')->willReturn($this->getApiPlansConfigResult($this->getArrayPlansKey()));
        $this->assertTrue($this->createPaymentPlansHelper()->paymentTriggerIsAllowed());
    }
    public function testPaymentTriggerIsNotAllowedWithAllNull(): void
    {
        $basePlans = $this->getArrayPlansKey();
        $basePlans[2]['trigger'] = null;
        $this->configHelper->method('getBaseApiPlansConfig')->willReturn($this->getApiPlansConfigResult($basePlans));
        $this->assertFalse($this->createPaymentPlansHelper()->paymentTriggerIsAllowed());
    }

    private function createPaymentPlansHelper(): PaymentPlansHelper
    {
        return new PaymentPlansHelper(...$this->getDependency());
    }

    private function getApiPlansConfigMock($plansConfig): array
    {
        $apiPlansMock = [];
        foreach ($plansConfig as $plan) {
            $apiPlansMock[] = $this->apiPlanFactory($plan);
        }
        return $apiPlansMock;
    }
    private function getApiPlansConfigResult($plansConfig): array
    {
        $apiPlansResultMock = [];
        foreach ($plansConfig as $plan) {
            $apiPlansResultMock[$plan['key']] = $this->apiPlanFactory($plan);
        }
        return $apiPlansResultMock;
    }

    private function apiPlanFactory($plan): FeePlan
    {
        preg_match('!general:([0-9]+):([0-9]+):([0-9]+)!', $plan['key'], $matches);
        $planData = [
                'allowed' => $plan['allowed'],
                'available_in_pos' => true,
                'capped' => false,
                'customer_fee_fixed' => 0,
                'customer_fee_variable' => 0,
                'customer_lending_rate' => 0,
                'deferred_days' => $matches[2],
                'deferred_months' => $matches[3],
                'deferred_trigger_bypass_scoring' => false,
                'deferred_trigger_limit_days' => $plan['trigger'],
                'first_installment_ratio' => null,
                'installments_count' => $matches[1],
                'is_under_maximum_interest_regulated_rate' => true,
                'kind' => 'general',
                'max_purchase_amount' => 200000,
                'merchant' => 'merchant_11tqLqZ6gQgUg6jrkXSrz7rGdIMuI5oImX',
                'merchant_fee_variable' => 75,
                'merchant_fee_fixed' => 0,
                'min_purchase_amount' => 5000,
                'payout_on_acceptance' => false
        ];
        return new FeePlan($planData);
    }
}
