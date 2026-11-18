<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Adminhtml\Config\ApiKey;

use Alma\API\Entities\Merchant;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\Availability;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Adminhtml\Config\ApiKey\LiveAPIKeyValue;
use Alma\MonthlyPayments\Model\Adminhtml\Config\ApiKey\TestAPIKeyValue;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\Manager as MessageManager;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;

class ApiKeyValueTest extends TestCase
{
    const SECRET_VALUE = '******';
    private $context;
    private $registry;
    private $scope;
    private $typeListInterface;
    private $encryptorInterface;
    private $availability;
    private $messageManager;
    private $apiConfigHelper;
    private $logger;
    private $configHelper;
    public function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->registry = $this->createMock(Registry::class);
        $this->scope = $this->createMock(ScopeConfigInterface::class);
        $this->typeListInterface = $this->createMock(TypeListInterface::class);
        $this->encryptorInterface = $this->createMock(EncryptorInterface::class);
        $this->availability = $this->createMock(Availability::class);
        $this->messageManager = $this->createMock(MessageManager::class);
        $this->apiConfigHelper = $this->createMock(ApiConfigHelper::class);
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
        $this->mockGetMerchant();
        $apiKeyObject = $this->createPartialMockApiKeyObject();
        $apiKeyObject->method('hasDataChanges')->willReturn(false);
        $apiKeyObject->method('getValue')->willReturn('test_key_1234567890');
        $apiKeyObject->expects($this->once())->method('disallowDataSave');
        $this->assertNull($apiKeyObject->beforeSave());
    }

    public function testGetApiTestModeForStars(): void
    {
        $ApiKeyObject = $this->createPartialMockApiKeyObject();
        $ApiKeyObject->method('hasDataChanges')->willReturn(false);
        $ApiKeyObject->method('getValue')->willReturn(self::SECRET_VALUE);
        $ApiKeyObject->method('getApiKeyType')->willReturn('test');
        $this->apiConfigHelper->expects($this->once())->method('getTestKey');
        $this->assertNull($ApiKeyObject->beforeSave());
    }
    public function testGetApiLiveModeForStars(): void
    {
        $ApiKeyObject = $this->createPartialMockApiKeyObject();
        $ApiKeyObject->method('hasDataChanges')->willReturn(false);
        $ApiKeyObject->method('getValue')->willReturn(self::SECRET_VALUE);
        $ApiKeyObject->method('getApiKeyType')->willReturn('live');
        $this->apiConfigHelper->expects($this->once())->method('getLiveKey');
        $this->assertNull($ApiKeyObject->beforeSave());
    }
    public function testNoChangeGetMerchantAndSaveData(): void
    {
        $this->mockGetMerchant();

        $ApiKeyObject = $this->createPartialMockApiKeyObject();
        $ApiKeyObject->method('hasDataChanges')->willReturn(false);
        $ApiKeyObject->method('getValue')->willReturn(self::SECRET_VALUE);

        $this->configHelper->expects($this->once())
            ->method('saveMerchantId');

        $ApiKeyObject->expects($this->once())->method('disallowDataSave');
        $this->assertNull($ApiKeyObject->beforeSave());

    }


    public function testStarsValueReturnEmptyAndDisallowSave(): void
    {
        $this->mockGetMerchant();

        $ApiKeyObject = $this->createPartialMockApiKeyObject();
        $ApiKeyObject->method('hasDataChanges')->willReturn(true);
        $ApiKeyObject->method('getValue')->willReturn(self::SECRET_VALUE);
        $ApiKeyObject->expects($this->once())->method('disallowDataSave');
        $ApiKeyObject->expects($this->never())->method('saveAndEncryptValue');
        $this->assertNull($ApiKeyObject->beforeSave());

    }

    public function testEmptyValueSave(): void
    {
        $this->mockGetMerchant();
        $this->configHelper->expects($this->exactly(1))->method('deleteConfig')->with('live_merchant_id', 0, 1);
        $ApiKeyObject = $this->createPartialMockApiKeyObject();
        $ApiKeyObject->method('hasDataChanges')->willReturn(true);
        $ApiKeyObject->method('getValue')->willReturn('');
        $ApiKeyObject->method('getScope')->willReturn(0);
        $ApiKeyObject->method('getScopeId')->willReturn(1);
        $ApiKeyObject->method('getMerchantIdPath')->willReturn('live_merchant_id');
        $ApiKeyObject->expects($this->never())->method('disallowDataSave');
        $ApiKeyObject->expects($this->once())->method('saveAndEncryptValue');
        $this->assertNull($ApiKeyObject->beforeSave());
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
        return $this->getMockBuilder(APIKeyValueData::class)
            ->setConstructorArgs($this->getConstructorDependency())
            ->onlyMethods([
                'hasDataChanges', 'getValue', 'getApiKeyType', 'getScope', 'getScopeId',
                'getMerchantIdPath', 'disallowDataSave', 'saveAndEncryptValue'
            ])
            ->getMock();
    }

    /**
     * Get merchant Mock
     * @return void
     */
    private function mockGetMerchant(bool $flag = true):void
    {
        $merchantMock = $this->createMock(Merchant::class);
        $merchantMock->cms_allow_inpage = $flag;
        $merchantMock->id = 'merchant_id';
        $this->availability->expects($this->once())
            ->method('getMerchant')
            ->willReturn($merchantMock);
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
            $this->apiConfigHelper
        ];
    }

}
