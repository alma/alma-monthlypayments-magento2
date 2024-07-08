<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Adminhtml\Source;

use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\StoreHelper;
use Alma\MonthlyPayments\Model\Adminhtml\Source\APIModes;
use PHPUnit\Framework\TestCase;

class APIModesTest extends TestCase
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfig;


    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->apiConfig = $this->createMock(ApiConfigHelper::class);
    }

    public function testDefaultWebsiteNoLiveKey(): void
    {
        $apiObject = $this->createApiModeObject();
        $this->assertEquals($this->getBaseResponseArray(), $apiObject->toOptionArray());
    }

    public function testDefaultWebsiteWithLiveKey(): void
    {
        $this->apiConfig->method('getLiveKey')->willReturn('live_1234567');
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
