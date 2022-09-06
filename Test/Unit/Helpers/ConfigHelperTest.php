<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Entities\FeePlan;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
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
