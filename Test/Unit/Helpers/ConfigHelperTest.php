<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Entities\FeePlan;
use Alma\API\Entities\Merchant;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\StoreHelper;
use Alma\MonthlyPayments\Test\Unit\Mocks\FeePlanFactoryMock;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\TestCase;

class ConfigHelperTest extends TestCase
{
    protected $scopeConfig;
    protected $context;
    protected $storeHelper;
    protected $writerInterface;
    protected $serializer;
    protected $typeList;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->context = $this->createMock(Context::class);
        $this->context->method('getScopeConfig')->willReturn($this->scopeConfig);
        $this->storeHelper = $this->createMock(StoreHelper::class);
        $this->writerInterface = $this->createMock(WriterInterface::class);
        $this->serializer = new Serialize();
        $this->typeList = $this->createMock(TypeListInterface::class);
    }

    public function testBaseApiPlansConfigForNullReturnValue()
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $feePlans = $this->createConfigHelper()->getBaseApiPlansConfig();
        $this->assertEquals([], $feePlans);
    }

    public function testBaseApiPlansConfigIsFeePlan(): void
    {
        $serializedPlans = $this->serializer->serialize(
            [
                FeePlanFactoryMock::dataFeePlanFactory(FeePlanFactoryMock::KEY_2),
                FeePlanFactoryMock::dataFeePlanFactory(FeePlanFactoryMock::KEY_3)
            ]
        );
        $this->scopeConfig->method('getValue')->willReturn($serializedPlans);

        $feePlans = $this->createConfigHelper()->getBaseApiPlansConfig();
        foreach ($feePlans as $feePlan) {
            $this->assertInstanceOf(FeePlan::class, $feePlan);
        }
    }

    public function testCmsInsuranceNotExistSaveTrue(): void
    {
        $merchant = $this->createMock(Merchant::class);
        $this->writerInterface->expects($this->once())->method('save')->with(
            ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . InsuranceHelper::IS_ALLOWED_INSURANCE_PATH,
            1,
            '',
            1
        );
        $this->createConfigHelper()->saveIsAllowedInsurance($merchant, '', 1);
    }

    public function testCmsAllowInsuranceIsTrueSave1(): void
    {
        $merchant = $this->createMock(Merchant::class);
        $merchant->cms_insurance = true;
        $this->writerInterface->expects($this->once())->method('save')->with(
            ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . InsuranceHelper::IS_ALLOWED_INSURANCE_PATH,
            1,
            '',
            1
        );
        $this->createConfigHelper()->saveIsAllowedInsurance($merchant, '', 1);
    }

    public function testCmsAllowInsuranceIsFalseSave0(): void
    {
        $merchant = $this->createMock(Merchant::class);
        $merchant->cms_insurance = false;
        $this->writerInterface->expects($this->once())->method('save')->with(
            ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . InsuranceHelper::IS_ALLOWED_INSURANCE_PATH,
            0,
            '',
            1
        );
        $this->createConfigHelper()->saveIsAllowedInsurance($merchant, '', 1);
    }

    public function testIfNoMerchantSave0(): void
    {
        $this->writerInterface->expects($this->once())->method('save')->with(
            ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . InsuranceHelper::IS_ALLOWED_INSURANCE_PATH,
            0,
            '',
            1
        );
        $this->createConfigHelper()->saveIsAllowedInsurance(null, '', 1);
    }

    public function testSaveIsAllowedInsuranceValue0()
    {
        $this->writerInterface->expects($this->once())->method('save')->with(
            ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . InsuranceHelper::IS_ALLOWED_INSURANCE_PATH,
            0,
            '',
            1
        );
        $this->createConfigHelper()->saveIsAllowedInsuranceValue(0, '', 1);
    }

    public function testSaveIsAllowedInsuranceValue1()
    {
        $this->writerInterface->expects($this->once())->method('save')->with(
            ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . InsuranceHelper::IS_ALLOWED_INSURANCE_PATH,
            1,
            '',
            1
        );
        $this->createConfigHelper()->saveIsAllowedInsuranceValue(1, '', 1);
    }

    public function testClearInsuranceConfig(): void
    {
        $this->writerInterface->expects($this->once())
            ->method('save')
            ->with(
                ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . InsuranceHelper::ALMA_INSURANCE_CONFIG_CODE,
                null,
                '',
                1
            );
        $this->createConfigHelper()->clearInsuranceConfig('', 1);
    }


    private function createConfigHelper(): ConfigHelper
    {
        return new ConfigHelper(...$this->getDependency());
    }

    protected function getDependency(): array
    {
        return [
            $this->context,
            $this->storeHelper,
            $this->writerInterface,
            $this->serializer,
            $this->typeList
        ];
    }
}
