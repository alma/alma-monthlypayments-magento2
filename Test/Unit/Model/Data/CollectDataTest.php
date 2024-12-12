<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Data;

use Alma\API\Entities\MerchantData\CmsFeatures;
use Alma\API\Entities\MerchantData\CmsInfo;
use Alma\API\Lib\PayloadFormatter;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\CmsFeaturesDataHelper;
use Alma\MonthlyPayments\Helpers\CmsInfoDataHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Data\CollectData;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Rest\Response;
use PHPUnit\Framework\TestCase;

class CollectDataTest extends TestCase
{
    private $collectData;
    private $payloadFormatter;

    private $cmsInfoDataHelper;
    private $cmsFeaturesDataHelper;
    private $request;
    /**
     * @var Logger
     */
    private $logger;
    private $apiConfigHelper;

    private $config;

    /**
     * @var Response
     */
    private $response;

    /**
     * @return void
     */
    public function mockSignature(): void
    {
        $this->request->method('getHeader')->willReturn('0dd3cb4632c074ead0d0f346c75015c76ad4e1e115f01c7e0850dd5accb7b4b0');
        $this->apiConfigHelper->method('getActiveAPIKey')->willReturn('api_key_test');
        $this->config->method('getMerchantId')->willReturn('merchant_id_test');
    }

    protected function setUp(): void
    {
        $this->cmsInfoDataHelper = $this->createMock(CmsInfoDataHelper::class);
        $this->cmsFeaturesDataHelper = $this->createMock(CmsFeaturesDataHelper::class);
        $this->logger = $this->createMock(Logger::class);
        $this->request = $this->createMock(Request::class);
        $this->apiConfigHelper = $this->createMock(ApiConfigHelper::class);
        $this->config = $this->createMock(Config::class);
        $this->response = $this->createMock(Response::class);
        $this->response->method('setHeader')->willReturnSelf();
        $this->response->method('setBody')->willReturnSelf();
        $this->payloadFormatter = new PayloadFormatter();
        $this->collectData = new CollectData(
            $this->logger,
            $this->payloadFormatter,
            $this->cmsInfoDataHelper,
            $this->cmsFeaturesDataHelper,
            $this->request,
            $this->apiConfigHelper,
            $this->config,
            $this->response
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCollectDataDirectReturnIfNoSignatureInHeader()
    {
        $this->request->method('getHeader')->willReturn( null );
        $this->expectException(Exception::class);
        $this->collectData->collect();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCollectDataDirectReturnIfNoApiKey()
    {
        $this->request->method('getHeader')->willReturn( '0dd3cb4632c074ead0d0f346c75015c76ad4e1e115f01c7e0850dd5accb7b4b0' );
        $this->apiConfigHelper->method('getActiveAPIKey')->willReturn( '' );
        $this->expectException(Exception::class);
        $this->collectData->collect();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCollectDataDirectReturnIfNoMerchantID()
    {
        $this->request->method('getHeader')->willReturn( '0dd3cb4632c074ead0d0f346c75015c76ad4e1e115f01c7e0850dd5accb7b4b0' );
        $this->apiConfigHelper->method('getActiveAPIKey')->willReturn('api_key_test');
        $this->config->method('getMerchantId')->willReturn(null);
        $this->expectException(Exception::class);
        $this->collectData->collect();
    }

    /**
     * Unit Test
     *
     * @return void
     * @throws Exception
     */
    public function testDataObjectLogic()
    {
        $this->mockSignature();
        $formater = $this->createMock(PayloadFormatter::class);
        $collectData = new CollectData(
            $this->logger,
            $formater,
            $this->cmsInfoDataHelper,
            $this->cmsFeaturesDataHelper,
            $this->request,
            $this->apiConfigHelper,
            $this->config,
            $this->response
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

}
