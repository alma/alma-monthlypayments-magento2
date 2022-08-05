<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Entities\FeePlan;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlanConfig;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlanConfigInterface;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterface;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterfaceFactory;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Magento\Framework\Message\Manager as MessageManager;
use PHPUnit\Framework\TestCase;

class PaymentPlansHelperTest extends TestCase
{
    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->plansConfigFactory = $this->createMock(PaymentPlansConfigInterfaceFactory::class);
        $this->messageManager = $this->createMock(MessageManager::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);
    }

    public function testSaveBaseApiPlanConfigWriteWithParams(): void
    {
        $arrayPlansKey = ['general:1:0:0','general:2:0:0','general:3:0:0'];
        $paymentPlanConfigInterfaceMock = $this->createMock(PaymentPlansConfigInterface::class);
        $paymentPlanConfigInterfaceMock->method('updateFromApi');
        $paymentPlanConfigInterfaceMock->method('getPlans')->willReturn($this->getApiPlansConfigMock($arrayPlansKey));
        $this->plansConfigFactory->method('create')->willReturn($paymentPlanConfigInterfaceMock);
        $this->configHelper->expects($this->once())->method('saveBasePlansConfig')->with($this->getApiPlansConfigResult($arrayPlansKey));
        $this->assertNull($this->createPaymentPlansHelper()->saveBaseApiPlanConfig());
    }

    private function createPaymentPlansHelper() : PaymentPlansHelper
    {
        return new PaymentPlansHelper(...$this->getDependency());
    }

    private function getDependency(): array
    {
        return [
            $this->logger,
            $this->plansConfigFactory,
            $this->messageManager,
            $this->configHelper
        ];
    }
    private function getApiPlansConfigMock($plans): array
    {
        $apiPlansMock = [];
        foreach ($plans as $key) {
            $apiPlansMock[$key] = $this->apiPlanFactory($key);
        }
        return $apiPlansMock;
    }
    private function getApiPlansConfigResult($plans): array
    {
        $apiPlansResultMock = [];
        foreach ($plans as $key) {
            $apiPlansResultMock[$key] = $this->apiPlanFactory($key)->toArray();
        }
        return $apiPlansResultMock;
    }

    private function apiPlanFactory($key): PaymentPlanConfigInterface
    {
        preg_match('!general:([0-9]+):([0-9]+):([0-9]+)!', $key, $matches);
        $planData = [
                'allowed' => true,
                'available_in_pos' => true,
                'capped' => false,
                'customer_fee_fixed' => 0,
                'customer_fee_variable' => 0,
                'customer_lending_rate' => 0,
                'deferred_days' => $matches[2],
                'deferred_months' => $matches[3],
                'deferred_trigger_bypass_scoring' => false,
                'deferred_trigger_limit_days' => null,
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
        $feePlanData = new FeePlan($planData);
        $paymentPlanConfig = new PaymentPlanConfig(PaymentPlanConfig::defaultConfigForFeePlan($feePlanData));
        return $paymentPlanConfig;
    }
}
