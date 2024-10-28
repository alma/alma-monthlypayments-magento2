<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Adminhtml\Config;

use Alma\MonthlyPayments\Helpers\CollectCmsConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Adminhtml\Config\SendCollectUrlBackendModel;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;

class SendCollectUrlBackendModelTest extends TestCase
{
    private $logger;
    private $sendCollectUrlBackendModel;
    private $collectCmsConfigHelper;

    protected function setUp(): void
    {
        $context = $this->createMock(Context::class);
        $context->method('getEventDispatcher')
            ->willReturn($this->createMock(\Magento\Framework\Event\Manager::class));
        $registry = $this->createMock(Registry::class);
        $scopeConfiguration = $this->createMock(ScopeConfigInterface::class);
        $typeList = $this->createMock(TypeListInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->collectCmsConfigHelper = $this->createMock(CollectCmsConfigHelper::class);
        $this->sendCollectUrlBackendModel = new SendCollectUrlBackendModel(
            $context,
            $registry,
            $scopeConfiguration,
            $typeList,
            $this->logger,
            $this->collectCmsConfigHelper
        );
    }

    public function testAfterSaveNotSendIfNotNecessary()
    {
        $this->collectCmsConfigHelper->expects($this->once())->method('getSendCollectUrlStatus')->willReturn((string)time());
        $this->collectCmsConfigHelper->expects($this->never())->method('sendIntegrationsConfigurationsUrl');
        $this->sendCollectUrlBackendModel->afterSave();
    }

    public function testAfterSaveSendIfNecessary()
    {
        $this->collectCmsConfigHelper->expects($this->once())->method('getSendCollectUrlStatus')->willReturn(null);
        $this->collectCmsConfigHelper->expects($this->once())->method('sendIntegrationsConfigurationsUrl');
        $this->sendCollectUrlBackendModel->afterSave();
    }


}
