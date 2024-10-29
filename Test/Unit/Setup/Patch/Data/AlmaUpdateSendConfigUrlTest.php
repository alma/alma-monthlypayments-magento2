<?php

namespace Alma\MonthlyPayments\Test\Unit\Setup\Patch\Data;

use Alma\MonthlyPayments\Helpers\CollectCmsConfigHelper;
use Alma\MonthlyPayments\Setup\Patch\Data\AlmaUpdateSendConfigUrl;
use PHPUnit\Framework\TestCase;

class AlmaUpdateSendConfigUrlTest extends TestCase
{
    private $collectCmsConfigHelper;
    private $dataPatchSendConfigUrl;

    protected function setUp(): void
    {
        $this->collectCmsConfigHelper = $this->createMock(CollectCmsConfigHelper::class);
        $this->dataPatchSendConfigUrl = new AlmaUpdateSendConfigUrl($this->collectCmsConfigHelper);
    }

    public function testDataPatchNotSendIfNotNecessary()
    {
        $this->collectCmsConfigHelper->expects($this->once())->method('getSendCollectUrlStatus')->willReturn((string)time());
        $this->collectCmsConfigHelper->expects($this->never())->method('sendIntegrationsConfigurationsUrl');
        $this->dataPatchSendConfigUrl->apply();
    }

    public function testPatchSendIfNecessary()
    {
        $this->collectCmsConfigHelper->expects($this->once())->method('getSendCollectUrlStatus')->willReturn(null);
        $this->collectCmsConfigHelper->expects($this->once())->method('sendIntegrationsConfigurationsUrl');
        $this->dataPatchSendConfigUrl->apply();
    }
}
