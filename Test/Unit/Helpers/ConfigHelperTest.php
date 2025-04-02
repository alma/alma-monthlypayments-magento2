<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Entities\FeePlan;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\StoreHelper;
use Alma\MonthlyPayments\Test\Unit\Mocks\FeePlanFactoryMock;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Serialize;
use PHPUnit\Framework\TestCase;

class ConfigHelperTest extends TestCase
{
    protected $scopeConfig;
    protected $context;
    protected $storeHelper;
    protected $writerInterface;
    protected $serializer;
    protected $typeList;
    protected $cacheManager;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->context = $this->createMock(Context::class);
        $this->context->method('getScopeConfig')->willReturn($this->scopeConfig);
        $this->storeHelper = $this->createMock(StoreHelper::class);
        $this->writerInterface = $this->createMock(WriterInterface::class);
        $this->serializer = new Serialize();
        $this->typeList = $this->createMock(TypeListInterface::class);
        $this->cacheManager = $this->createMock(Manager::class);

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
            $this->typeList,
            $this->cacheManager
        ];
    }
}
