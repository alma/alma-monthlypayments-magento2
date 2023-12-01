<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Entities\FeePlan;
use Alma\API\Entities\Merchant;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\StoreHelper;
use Alma\MonthlyPayments\Test\Unit\Mocks\FeePlanFactoryMock;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
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
        $this->scopeConfig->method('getValue')->willReturn('');
        $this->context = $this->createMock(Context::class);
        $this->context->method('getScopeConfig')->willReturn($this->scopeConfig);
        $this->storeHelper = $this->createMock(StoreHelper::class);
        $this->writerInterface = $this->createMock(WriterInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->typeList = $this->createMock(TypeListInterface::class);
    }

    public function testBaseApiPlansConfigIsFeePlan(): void
    {
        $this->serializer->method('unserialize')->willReturn(
            [
                FeePlanFactoryMock::dataFeePlanFactory(FeePlanFactoryMock::KEY_2),
                FeePlanFactoryMock::dataFeePlanFactory(FeePlanFactoryMock::KEY_3),
            ]
        );
        $feePlans = $this->createConfigHelper()->getBaseApiPlansConfig();
        foreach ($feePlans as $feePlan) {
            $this->assertInstanceOf(FeePlan::class, $feePlan);
        }
    }
    public function testCmsInsuranceNotExistSaveTrue():void
    {
        $merchant = $this->createMock(Merchant::class);
        $this->writerInterface->expects($this->once())->method('save')->with(
            ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . ConfigHelper::IS_ALLOWED_INSURANCE_PATH,
            1,
            0,
            1
        );
        $this->createConfigHelper()->saveIsAllowedInsurance( $merchant, 0, 1);
    }
    public function testCmsAllowInsuranceIsTrueSave1():void
    {
        $merchant = $this->createMock(Merchant::class);
        $merchant->cms_insurance = true;
        $this->writerInterface->expects($this->once())->method('save')->with(
            ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . ConfigHelper::IS_ALLOWED_INSURANCE_PATH,
            1,
            0,
            1
        );
        $this->createConfigHelper()->saveIsAllowedInsurance( $merchant, 0, 1);
    }
    public function testCmsAllowInsuranceIsFalseSave0():void
    {
        $merchant = $this->createMock(Merchant::class);
        $merchant->cms_insurance = false;
        $this->writerInterface->expects($this->once())->method('save')->with(
            ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . ConfigHelper::IS_ALLOWED_INSURANCE_PATH,
            0,
            0,
            1
        );
        $this->createConfigHelper()->saveIsAllowedInsurance( $merchant, 0, 1);
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
