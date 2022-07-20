<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Adminhtml\Config\ApiKey;

use Alma\API\Entities\Merchant;
use Alma\MonthlyPayments\Helpers\Availability;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Adminhtml\Config\ApiKey\APIKeyValue;
use Alma\MonthlyPayments\Model\Adminhtml\Config\ApiKey\LiveAPIKeyValue;
use Alma\MonthlyPayments\Model\Adminhtml\Config\ApiKey\TestAPIKeyValue;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\Manager as MessageManager;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Mockery;
use PHPUnit\Framework\TestCase;

class ApiKeyValueTest extends TestCase
{
    public function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->registry = $this->createMock(Registry::class);
        $this->scope = $this->createMock(ScopeConfigInterface::class);
        $this->typeListInterface = $this->createMock(TypeListInterface::class);
        $this->encryptorInterface = $this->createMock(EncryptorInterface::class);
        $this->availability = $this->createMock(Availability::class);
        $this->messageManager = $this->createMock(MessageManager::class);
        $this->logger = $this->createMock(Logger::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);
    }

    public function testGetTestApiKeyNameValue(): void
    {
        $this->assertEquals('Test API key', $this->createTestApiKeyObject()->getApiKeyName());
    }

    public function testGetLiveApiKeyNameValue(): void
    {
        $this->assertEquals('Live API key', $this->createLiveApiKeyObject()->getApiKeyName());
    }

    public function testNoChangeReturnEmptyAndDisallowSave(): void
    {
        $ApiKeyObject = $this->createPartialMockApiKeyObject();
        $ApiKeyObject->expects('hasDataChanges')->andReturn(false);
        $ApiKeyObject->expects('getValue')->andReturn('test_key_1234567890');
        $this->assertNull($ApiKeyObject->beforeSave());
        $ApiKeyObject->shouldHaveReceived('disallowDataSave')->once();
    }

    public function testStarsValueReturnEmptyAndDisallowSave(): void
    {
        $ApiKeyObject = $this->createPartialMockApiKeyObject();
        $ApiKeyObject->expects('hasDataChanges')->andReturn(true);
        $ApiKeyObject->expects('getValue')->andReturn('******');
        $this->assertNull($ApiKeyObject->beforeSave());
        $ApiKeyObject->shouldHaveReceived('disallowDataSave')->once();
        $ApiKeyObject->shouldNotHaveReceived('saveAndEncryptValue');

    }
    public function testEmptyValueSave(): void
    {
        $merchantMock = $this->createMock(Merchant::class);
        $merchantMock->id = 'merchant_id';
        $this->availability->expects($this->once())
            ->method('getMerchant')
            ->willReturn($merchantMock);
        $this->configHelper->expects($this->once())
            ->method('saveMerchantId');
        $ApiKeyObject = $this->createPartialMockApiKeyObject();
        $ApiKeyObject->expects('hasDataChanges')->andReturn(true);
        $ApiKeyObject->expects('getValue')->andReturn('');
        $this->assertNull($ApiKeyObject->beforeSave());
        $ApiKeyObject->shouldNotHaveReceived('disallowDataSave');
        $ApiKeyObject->shouldHaveReceived('saveAndEncryptValue')->once();
    }

    private function createTestApiKeyObject(): TestAPIKeyValue
    {
        return new TestAPIKeyValue(...$this->getConstructorDependency());
    }

    private function createLiveApiKeyObject(): LiveAPIKeyValue
    {
        return new LiveAPIKeyValue(...$this->getConstructorDependency());
    }

    private function createPartialMockApiKeyObject()
    {
        return Mockery::mock(APIKeyValue::class, $this->getConstructorDependency())->makePartial()->shouldAllowMockingProtectedMethods();
    }

    private function getConstructorDependency(): array
    {
        return [
            $this->context,
            $this->registry,
            $this->scope,
            $this->typeListInterface,
            $this->encryptorInterface,
            $this->availability,
            $this->messageManager,
            $this->configHelper,
            $this->logger
        ];
    }

}
