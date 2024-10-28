<?php /** @noinspection PhpVoidFunctionResultUsedInspection */

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Client;
use Alma\API\Endpoints\Merchants;
use Alma\API\Exceptions\RequestException;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\CollectCmsConfigHelper;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\StoreHelper;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\TestCase;

class CollectCmsConfigHelperTest extends TestCase
{
    private $context;
    private $scopeConfig;
    private $storeHelper;
    private $writerInterface;
    private $serializer;
    private $typeList;
    private $collectCmlsConfigHelper;
    private $almaClient;
    private $merchantEndpoint;
    private $logger;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->context = $this->createMock(Context::class);
        $this->context->method('getScopeConfig')->willReturn($this->scopeConfig);

        $this->storeHelper = $this->createMock(StoreHelper::class);
        $this->writerInterface = $this->createMock(WriterInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->typeList = $this->createMock(TypeListInterface::class);

        $this->merchantEndpoint = $this->createMock(Merchants::class);
        $client = $this->createMock(Client::class);
        $client->merchants = $this->merchantEndpoint;
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->almaClient->method('getDefaultClient')->willReturn($client);

        $this->collectCmlsConfigHelper = new CollectCmsConfigHelper(
            $this->context,
            $this->storeHelper,
            $this->writerInterface,
            $this->serializer,
            $this->typeList,
            $this->almaClient,
            $this->logger
        );
    }

    public function testSendIntegrationsConfigurationsUrlWithGoodParamsWriteConfig()
    {
        $this->writerInterface->expects($this->once())->method('save')->with('payment/alma_monthly_payments/send_collect_url_status', time());
        $this->merchantEndpoint->expects($this->once())->method('sendIntegrationsConfigurationsUrl')->with('/V1/alma/config/collect');
        $this->assertNull($this->collectCmlsConfigHelper->sendIntegrationsConfigurationsUrl());
    }

    public function testNoApiKeyInConfig()
    {
        $this->writerInterface->expects($this->never())->method('save');
        $this->almaClient->method('getDefaultClient')->willThrowException(new AlmaClientException('No API key'));
        $this->logger->expects($this->never())->method('warning');
        $this->assertNull($this->collectCmlsConfigHelper->sendIntegrationsConfigurationsUrl());
    }

    public function testErrorOnSendConfigurationNotSaveTimeStamp()
    {
        $this->writerInterface->expects($this->never())->method('save');
        $this->merchantEndpoint->expects($this->once())
            ->method('sendIntegrationsConfigurationsUrl')
            ->with('/V1/alma/config/collect')
            ->willThrowException(new RequestException('Error in send'));
        $this->logger->expects($this->once())->method('warning');
        $this->assertNull($this->collectCmlsConfigHelper->sendIntegrationsConfigurationsUrl());
    }


}
