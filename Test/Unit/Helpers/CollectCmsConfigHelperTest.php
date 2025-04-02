<?php /** @noinspection PhpVoidFunctionResultUsedInspection */

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Client;
use Alma\API\Endpoints\Configuration;
use Alma\API\Exceptions\RequestException;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\CollectCmsConfigHelper;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\StoreHelper;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
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
    private $configurationEndpoint;
    private $logger;

    private $urlBuilder;

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
        $this->urlBuilder = $this->createMock(UrlInterface::class);

        $this->configurationEndpoint = $this->createMock(Configuration::class);
        $client = $this->createMock(Client::class);
        $client->configuration = $this->configurationEndpoint;
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->almaClient->method('getDefaultClient')->willReturn($client);
        $manager = $this->createMock(Manager::class);
        $manager->method('getAvailableTypes')->willReturn([]);

        $this->collectCmlsConfigHelper = new CollectCmsConfigHelper(
            $this->context,
            $this->storeHelper,
            $this->writerInterface,
            $this->serializer,
            $this->typeList,
            $this->almaClient,
            $this->urlBuilder,
            $this->logger,
            $manager
        );
    }

    public function testSendIntegrationsConfigurationsUrlWithGoodParamsWriteConfig()
    {
        $this->urlBuilder->method('getBaseUrl')->willReturn('https://baseurl.com/');
        $this->writerInterface->expects($this->once())->method('save')->with('payment/alma_monthly_payments/send_collect_url_status', time());
        $this->configurationEndpoint->expects($this->once())->method('sendIntegrationsConfigurationsUrl')->with('https://baseurl.com/rest/V1/alma/config/collect');
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
        $this->configurationEndpoint->expects($this->once())
            ->method('sendIntegrationsConfigurationsUrl')
            ->willThrowException(new RequestException('Error in send'));
        $this->logger->expects($this->once())->method('warning');
        $this->assertNull($this->collectCmlsConfigHelper->sendIntegrationsConfigurationsUrl());
    }


}
