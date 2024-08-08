<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlanConfig;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterface;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Alma\MonthlyPayments\Test\Unit\Mocks\FeePlanConfigFactoryMock;
use Alma\MonthlyPayments\Test\Unit\Mocks\FeePlanFactoryMock;
use Magento\Framework\Message\Manager as MessageManager;
use PHPUnit\Framework\TestCase;

class PaymentPlansHelperTest extends TestCase
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var PaymentPlansConfigInterface
     */
    private $paymentPlansConfig;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var PaymentPlanConfig
     */
    private $paymentPlanConfig;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->paymentPlanConfig = $this->createMock(PaymentPlanConfig::class);
        $this->paymentPlansConfig = $this->createMock(PaymentPlansConfigInterface::class);
        $this->messageManager = $this->createMock(MessageManager::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);
    }

    private function getDependency(): array
    {
        return [
            $this->logger,
            $this->paymentPlanConfig,
            $this->paymentPlansConfig,
            $this->messageManager,
            $this->configHelper
        ];
    }

    private function getArrayPlansKey(): array
    {
        return [
            ['key' => 'general:1:0:0', 'allowed' => true, 'trigger' => null, 'minAmount' => 100],
            ['key' => 'general:2:0:0', 'allowed' => false, 'trigger' => null, 'minAmount' => 5000],
            ['key' => 'general:3:0:0', 'allowed' => true, 'trigger' => '30', 'minAmount' => 5000]
        ];
    }

    public function testSaveBaseApiPlanConfigWriteWithParams(): void
    {
        $arrayPlansKey = $this->getArrayPlansKey();

        $mockFeePlans = $this->getApiPlansConfigMock($arrayPlansKey);
        $this->paymentPlansConfig->method('getFeePlansFromApi')->willReturn($mockFeePlans);

        $this->configHelper->expects($this->once())
            ->method('saveBasePlansConfig')
            ->with($this->getApiPlansConfigResult($arrayPlansKey));
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
            $apiPlansMock[] = FeePlanFactoryMock::feePlanFactory(
                $plan['key'],
                $plan['allowed'],
                $plan['trigger'],
                $plan['minAmount']
            );
        }

        return $apiPlansMock;
    }

    private function getApiPlansConfigResult($plansConfig): array
    {
        $apiPlansResultMock = [];
        foreach ($plansConfig as $plan) {
            $apiPlansResultMock[$plan['key']] = FeePlanFactoryMock::feePlanFactory(
                $plan['key'],
                $plan['allowed'],
                $plan['trigger'],
                $plan['minAmount']
            );
        }

        return $apiPlansResultMock;
    }

    /**
     * @dataProvider formatFeePlanForConfigSave
     *
     * @param $apiFeePlan
     * @param $inputConfig
     * @param $result
     *
     * @return void
     */
    public function testMergeApiConfigAndInputForSaveInConfig($apiFeePlan, $inputConfig, $result): void
    {
        $this->assertEquals($result, $this->createPaymentPlansHelper()->formatFeePlanConfigForSave($apiFeePlan, $inputConfig));
    }

    /**
     * @dataProvider formatFeePlanDataProviderForDisplay
     *
     * @param $apiFeePlan
     * @param $configFeePlan
     * @param $result
     *
     * @return void
     */
    public function testFormatFeePlanConfigForBackOfficeDisplay($apiFeePlan, $configFeePlan, $result): void
    {
        $this->assertEquals($result, $this->createPaymentPlansHelper()->formatLocalFeePlanConfig($apiFeePlan, $configFeePlan));
    }

    /**
     * @dataProvider getInPagePlanKeys
     * @param $keyPlan
     * @param $result
     * @return void
     */
    public function testIsInPageAllowedWithInPageActivated($keyPlan, $result): void
    {
        $this->configHelper->expects($this->once())
            ->method('isInPageEnabled')
            ->willReturn(true);
        $this->assertEquals($result, $this->createPaymentPlansHelper()->isInPageAllowed($keyPlan));
    }

    /**
     * @dataProvider getInPagePlanKeys
     * @param $keyPlan
     * @return void
     */
    public function testIsInPageAllowedWithoutInPageActivated($keyPlan): void
    {
        $this->configHelper->expects($this->once())
            ->method('isInPageEnabled')
            ->willReturn(false);
        $this->assertFalse($this->createPaymentPlansHelper()->isInPageAllowed($keyPlan));
    }

    /**
     * @return array[]
     */
    public function getInPagePlanKeys(): array
    {
        return [
            'test paynow' => [
                'keyPlan' => 'general:1:0:0',
                'result' => true,
            ],
            'test p2x' => [
                'keyPlan' => 'general:2:0:0',
                'result' => true,
            ],
            'test p3x' => [
                'keyPlan' => 'general:3:0:0',
                'result' => true,
            ],
            'test p4x' => [
                'keyPlan' => 'general:4:0:0',
                'result' => true,
            ],
            'test p6x' => [
                'keyPlan' => 'general:6:0:0',
                'result' => false,
            ],
            'test p12x' => [
                'keyPlan' => 'general:6:0:0',
                'result' => false,
            ],
            'test deferred 15d' => [
                'keyPlan' => 'general:1:15:0',
                'result' => true,
            ],
            'test deferred 30d' => [
                'keyPlan' => 'general:1:30:0',
                'result' => true,
            ],
        ];
    }

    public function formatFeePlanDataProviderForDisplay(): array
    {
        $dataToDisplayDefaultValue = FeePlanConfigFactoryMock::getDefaultDataPlan();

        $dataToDisplay3InstallmentAutoEnable = FeePlanConfigFactoryMock::getDefaultDataPlan();
        $dataToDisplay3InstallmentAutoEnable['key'] = FeePlanFactoryMock::KEY_3;
        $dataToDisplay3InstallmentAutoEnable['enabled'] = 1;
        $dataToDisplay3InstallmentAutoEnable['pnx_label'] = 'Pay in 3 installments';

        $dataToDisplayDeferred = FeePlanConfigFactoryMock::getDefaultDataPlan();
        $dataToDisplayDeferred['key'] = FeePlanFactoryMock::KEY_15;
        $dataToDisplayDeferred['pnx_label'] = 'Pay later - D+15';

        $dataToDisplayDisablePlan = FeePlanConfigFactoryMock::getDefaultDataPlan();
        $dataToDisplayDisablePlan['enabled'] = 1;
        $dataToDisplayDisablePlan['custom_min_purchase_amount'] = 65;
        $dataToDisplayDisablePlan['custom_max_purchase_amount'] = 2500;

        return [
            'Auto enable 3 installment plan if config is empty' => [
                'apiFeePlan' => FeePlanFactoryMock::feePlanFactory(FeePlanFactoryMock::KEY_3, true),
                'configPlan' => null,
                'resultPlanForDisplay' => FeePlanConfigFactoryMock::feePlanConfigForDisplayFactory($dataToDisplay3InstallmentAutoEnable),
            ],
            'Deferred plan Config is empty' => [
                'apiFeePlan' => FeePlanFactoryMock::feePlanFactory(FeePlanFactoryMock::KEY_15, true),
                'configPlan' => null,
                'resultPlanForDisplay' => FeePlanConfigFactoryMock::feePlanConfigForDisplayFactory($dataToDisplayDeferred),
            ],
            'Installment plan Config is empty' => [
                'apiFeePlan' => FeePlanFactoryMock::feePlanFactory(FeePlanFactoryMock::KEY_2, true),
                'configPlan' => null,
                'resultPlanForDisplay' => FeePlanConfigFactoryMock::feePlanConfigForDisplayFactory($dataToDisplayDefaultValue),
            ],
            'Plan is enabled with non default custom value in config' => [
                'apiFeePlan' => FeePlanFactoryMock::feePlanFactory(FeePlanFactoryMock::KEY_2, true),
                'configPlan' => FeePlanConfigFactoryMock::feePlanConfigFactory(FeePlanFactoryMock::KEY_2, 1, null, 6500, 250000),
                'resultPlanForDisplay' => FeePlanConfigFactoryMock::feePlanConfigForDisplayFactory($dataToDisplayDisablePlan),
            ]
        ];
    }

    public function formatFeePlanForConfigSave(): array
    {
        return [
            'activate fee plan' => [
                'apiFeePlan' => FeePlanFactoryMock::feePlanFactory(FeePlanFactoryMock::KEY_15, true),
                'inputConfig' => [
                    'enabled' => 1,
                    'custom_min_purchase_amount' => 50,
                    'custom_max_purchase_amount' => 2000
                ],
                'mergeResult' => FeePlanConfigFactoryMock::feePlanConfigFactory(FeePlanFactoryMock::KEY_15, 1)
            ],
            'non active fee plan' => [
                'apiFeePlan' => FeePlanFactoryMock::feePlanFactory(FeePlanFactoryMock::KEY_15, true),
                'inputConfig' => [
                    'enabled' => 0,
                    'custom_min_purchase_amount' => 50,
                    'custom_max_purchase_amount' => 2000
                ],
                'mergeResult' => FeePlanConfigFactoryMock::feePlanConfigFactory(FeePlanFactoryMock::KEY_15)
            ],
            'Active fee plan with min and max change in' => [
                'apiFeePlan' => FeePlanFactoryMock::feePlanFactory(FeePlanFactoryMock::KEY_15, true),
                'inputConfig' => [
                    'enabled' => 1,
                    'custom_min_purchase_amount' => 65,
                    'custom_max_purchase_amount' => 1200
                ],
                'mergeResult' => FeePlanConfigFactoryMock::feePlanConfigFactory(FeePlanFactoryMock::KEY_15, 1, null, 6500, 120000)
            ],
            'Active fee plan with min and max change Out' => [
                'apiFeePlan' => FeePlanFactoryMock::feePlanFactory(FeePlanFactoryMock::KEY_15, true),
                'inputConfig' => [
                    'enabled' => 1,
                    'custom_min_purchase_amount' => 35,
                    'custom_max_purchase_amount' => 2500
                ],
                'mergeResult' => FeePlanConfigFactoryMock::feePlanConfigFactory(FeePlanFactoryMock::KEY_15, 1)
            ],
            'Active fee plan with min and max change invert' => [
                'apiFeePlan' => FeePlanFactoryMock::feePlanFactory(FeePlanFactoryMock::KEY_15, true),
                'inputConfig' => [
                    'enabled' => 1,
                    'custom_min_purchase_amount' => 2500,
                    'custom_max_purchase_amount' => 32
                ],
                'mergeResult' => FeePlanConfigFactoryMock::feePlanConfigFactory(FeePlanFactoryMock::KEY_15, 1)
            ]
        ];
    }
}
