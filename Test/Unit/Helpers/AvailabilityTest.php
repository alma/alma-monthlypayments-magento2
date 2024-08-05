<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Client;
use Alma\API\Endpoints\Merchants;
use Alma\API\Entities\Merchant;
use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\Availability;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceFlagException;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class AvailabilityTest extends TestCase
{
    private $storeManager;
    private $almaClient;
    private $logger;
    private $apiConfigHelper;
    private $merchantsEndpoint;

    protected function setUp(): void
    {
        $clientMock = $this->createMock(Client::class);
        $this->merchantsEndpoint = $this->createMock(Merchants::class);
        $clientMock->merchants = $this->merchantsEndpoint;
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->almaClient->method('getDefaultClient')->willReturn($clientMock);
        $this->apiConfigHelper = $this->createMock(ApiConfigHelper::class);
        $this->logger = $this->createMock(Logger::class);
    }

    protected function tearDown(): void
    {
        $this->storeManager = null;
        $this->almaClient = null;
        $this->apiConfigHelper = null;
        $this->logger = null;
        $this->merchantsEndpoint = null;
    }

    private function createAvailability(): Availability
    {
        return new Availability(
            $this->storeManager,
            $this->almaClient,
            $this->apiConfigHelper,
            $this->logger
        );
    }

    public function testThrowAlmaInsuranceFlagExceptionIfMeThrowException():void
    {
        $this->merchantsEndpoint->method('me')->willThrowException(new RequestError('error'));
        $this->expectException(AlmaInsuranceFlagException::class);
        $this->createAvailability()->isMerchantInsuranceAvailable();
    }

    public function testGivenNoCmsInsuranceFlagInMeReturnTrue(): void
    {
        $meData = $this->extendedDataMe();
        $meData->cms_insurance = null;
        $this->merchantsEndpoint->method('me')->willReturn($meData);
        $this->assertTrue($this->createAvailability()->isMerchantInsuranceAvailable());
    }

    /**
     * @dataProvider extendedDataMeDataProvider
     * @param $meData
     * @param $result
     * @return void
     * @throws AlmaInsuranceFlagException
     */
    public function testGivenCmsInsuranceFlagInMeReturnFlagValue($meData, $result):void
    {
        $this->merchantsEndpoint->method('me')->willReturn($meData);
        $this->assertEquals($result, $this->createAvailability()->isMerchantInsuranceAvailable());
    }

    public function extendedDataMe($cmsInsurance = true)
    {
        return new Merchant([
            'id' => 'merchant_11mLCKp39by3Yb1VAAIAWWqSwYg8Q2Fy17',
            'name' => 'Alma',
            'country' => 'FR',
            'cms_insurance' => $cmsInsurance,
        ]);
    }

    public function extendedDataMeDataProvider()
    {
        return [
            'flag insurance true' => [
                'flag' => $this->extendedDataMe(true),
                'result' => true
            ],
            'flag insurance false' => [
                'flag' => $this->extendedDataMe(false),
                'result' => false
            ]
        ];
    }
}
