<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Adminhtml\Source;

use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Adminhtml\Source\APIModes;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class APIModesTest extends TestCase
{
    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->apiConfig = $this->createMock(ApiConfigHelper::class);
    }

    public function testDefaultWebsiteNoLiveKey(): void
    {
        $apiObject = $this->createApiModeObject();
        $this->assertEquals($this->getBaseResponseArray(), $apiObject->toOptionArray());
    }
    public function testDefaultWebsiteWithLiveKey(): void
    {
        $this->apiConfig->method('getLiveKey')->with(ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0)->willReturn('live_1234567');
        $apiObject = $this->createApiModeObject();
        $this->assertEquals($this->getLiveResponseArray(), $apiObject->toOptionArray());
    }
    public function testStoreViewWithLiveKey(): void
    {
        $this->request->expects($this->exactly(2))->method('getParam')->willReturnOnConsecutiveCalls(2, null);
        $this->apiConfig->method('getLiveKey')->with(ScopeInterface::SCOPE_STORES, 2)->willReturn('live_1234567');
        $apiObject = $this->createApiModeObject();
        $this->assertEquals($this->getLiveResponseArray(), $apiObject->toOptionArray());
    }
    public function testWebsiteWithLiveKey(): void
    {
        $this->request->expects($this->exactly(2))->method('getParam')->willReturnOnConsecutiveCalls(null, 1);
        $this->apiConfig->method('getLiveKey')->with(ScopeInterface::SCOPE_WEBSITES, 1)->willReturn('live_1234567');
        $apiObject = $this->createApiModeObject();
        $this->assertEquals($this->getLiveResponseArray(), $apiObject->toOptionArray());
    }


    private function createApiModeObject(): APIModes
    {
        return new APIModes(...$this->getConstructorDependency());
    }

    private function getConstructorDependency(): array
    {
        return [
            $this->logger,
            $this->request,
            $this->apiConfig,
        ];
    }
    private function getBaseResponseArray(): array
    {
        return [['value' => 'test', 'label' => __('Test')]];
    }
    private function getLiveResponseArray(): array
    {
        $resultArray = $this->getBaseResponseArray();
        $resultArray[] = ['value' => 'live', 'label' => __('Live')];
        return $resultArray;
    }
}
