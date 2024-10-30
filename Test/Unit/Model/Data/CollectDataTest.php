<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Data;

use Alma\API\Entities\MerchantData\CmsFeatures;
use Alma\API\Entities\MerchantData\CmsInfo;
use Alma\API\Lib\PayloadFormatter;
use Alma\MonthlyPayments\Helpers\CmsFeaturesDataHelper;
use Alma\MonthlyPayments\Helpers\CmsInfoDataHelper;
use Alma\MonthlyPayments\Helpers\CollectCmsConfigHelper;
use Alma\MonthlyPayments\Model\Data\CollectData;
use PHPUnit\Framework\TestCase;

class CollectDataTest extends TestCase
{
    private $collectData;
    private $payloadFormatter;

    private $cmsInfoDataHelper;
    private $cmsFeaturesDataHelper;

    protected function setUp(): void
    {
        $this->cmsInfoDataHelper = $this->createMock(CmsInfoDataHelper::class);
        $this->cmsFeaturesDataHelper = $this->createMock(CmsFeaturesDataHelper::class);
        $this->payloadFormatter = new PayloadFormatter();
        $this->collectData = new CollectData(
            $this->payloadFormatter,
            $this->cmsInfoDataHelper,
            $this->cmsFeaturesDataHelper
        );
    }

    /**
     * Unit Test
     *
     * @return void
     */
    public function testDataObjectLogic()
    {
        $formater = $this->createMock(PayloadFormatter::class);
        $collectData = new CollectData(
            $formater,
            $this->cmsInfoDataHelper,
            $this->cmsFeaturesDataHelper
        );

        $formater
            ->expects($this->once())
            ->method('formatConfigurationPayload')
            ->with(
                $this->isInstanceOf(CmsInfo::class),
                $this->isInstanceOf(CmsFeatures::class)
            );
        $this->assertNull($collectData->collect());
    }

    /**
     * Integration Test
     *
     * @return void
     */
    public function testDataFormaterReturnEmptyDataStructure()
    {
        $this->cmsInfoDataHelper
            ->expects($this->once())
            ->method('getCmsInfoData')
            ->willReturn([]);
        $this->cmsFeaturesDataHelper
            ->expects($this->once())
            ->method('getCmsFeaturesData')
            ->willReturn([]);
        $this->assertEquals(['cms_info' => [], 'cms_features' => []], $this->collectData->collect());
    }

}
