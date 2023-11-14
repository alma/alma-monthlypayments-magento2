<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Data\InsuranceConfig;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;

class InsuranceHelperTest extends TestCase
{
    /**
     * @var Context
     *
     */
    private $contextMock;
    /**
     * @var RequestInterface
     *
     */
    private $requestInterfaceMock;
    /**
     * @var ProductRepository
     *
     */
    private $productRepositoryMock;
    /**
     * @var Logger
     *
     */
    private $logger;
    /**
     * @var Json
     *
     */
    private $jsonMock;
    /**
     * @var InsuranceHelper
     */
    private $insuranceHelper;
    /**
     * @var ConfigHelper
     *
     */
    private $configHelper;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->requestInterfaceMock = $this->createMock(RequestInterface::class);
        $this->productRepositoryMock = $this->createMock(ProductRepository::class);
        $this->logger = $this->createMock(Logger::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->insuranceHelper = $this->createNewInsuranceHelper();

    }
    private function getConstructorDependency(): array
    {
        return [
            $this->contextMock,
            $this->requestInterfaceMock,
            $this->productRepositoryMock,
            $this->logger,
            $this->jsonMock,
            $this->configHelper
        ];
    }
    //
    public function testInsuranceConfigIsAnInsuranceConfigObject():void
    {
        $this->configHelper->expects($this->once())->method('getConfigByCode')->willReturn('');
        $this->assertTrue(get_class($this->insuranceHelper->getConfig()) == InsuranceConfig::class);
    }

    public function testInsuranceConfigGetDataInDb():void
    {
        $this->configHelper->expects($this->once())
            ->method('getConfigByCode')
            ->with(InsuranceHelper::ALMA_INSURANCE_CONFIG_CODE)
            ->willReturn('');
        $this->insuranceHelper->getConfig();
    }

   /**
     * @param $result
     * @param $dbValue
     * @return void
     * @dataProvider configObjectIsCreatedWithDbDataDataProvider
     */
    public function testConfigObjectIsCreatedWithDbData($activated, $pageActivated, $cartActivated,$popupActivated, $dbValue):void
    {
        $this->configHelper->expects($this->once())->method('getConfigByCode')->willReturn($dbValue);
        $insuranceObject = $this->insuranceHelper->getConfig();
        $this->assertEquals($activated, $insuranceObject->isActivated());
        $this->assertEquals($pageActivated, $insuranceObject->isPageActivated());
        $this->assertEquals($cartActivated, $insuranceObject->isCartActivated());
        $this->assertEquals($popupActivated, $insuranceObject->isPopupActivated());
    }

    private function configObjectIsCreatedWithDbDataDataProvider(): array
    {
        return [
            'Return false if no data in DB'  => [
                'activated' => false,
                'page_activated' => false,
                'cart_activated' => false,
                'popup_activated' => false,
                'db_value' => ''
            ],
            'Return false if null in DB'  => [
                'activated' => false,
                'page_activated' => false,
                'cart_activated' => false,
                'popup_activated' => false,
                'db_value' => null
            ],
            'Return false if key is not present and ignore unknown keys '  => [
                'activated' => false,
                'page_activated' => false,
                'cart_activated' => false,
                'popup_activated' => true,
                'db_value' => '{
                    "is_activated":true,
                    "is_on_product_page_activated":true,
                    "is_on_cart_page_activated":true,
                    "is_add_to_cart_popup_insurance_activated":true
                    }'
            ],
           'Return true all is true in DB'  => [
               'activated' => true,
               'page_activated' => true,
               'cart_activated' => true,
               'popup_activated' => true,
               'db_value' => '{
                    "is_insurance_activated":true,
                    "is_insurance_on_product_page_activated":true,
                    "is_insurance_on_cart_page_activated":true,
                    "is_add_to_cart_popup_insurance_activated":true
                    }'
           ],
           'Return false if all is false in DB'  => [
               'activated' => false,
               'page_activated' => false,
               'cart_activated' => false,
               'popup_activated' => false,
               'db_value' => '{
                    "is_insurance_activated":false,
                    "is_insurance_on_product_page_activated":false,
                    "is_insurance_on_cart_page_activated":false,
                    "is_add_to_cart_popup_insurance_activated":false
                    }'
           ],
            'Return good values'  => [
                'activated' => true,
                'page_activated' => false,
                'cart_activated' => true,
                'popup_activated' => false,
                'db_value' => '{
                    "is_insurance_activated":true,
                    "is_insurance_on_product_page_activated":false,
                    "is_insurance_on_cart_page_activated":true,
                    "is_add_to_cart_popup_insurance_activated":false
                    }'
            ]
        ];
    }

    public function testReturnArrayValueInDb():void
    {
        $dbValue = '{
                    "is_insurance_activated":true,
                    "is_insurance_on_product_page_activated":false,
                    "is_insurance_on_cart_page_activated":true,
                    "is_add_to_cart_popup_insurance_activated":false
                    }';
        $result = [
            'is_insurance_activated' => true,
            'is_insurance_on_product_page_activated' => false,
            'is_insurance_on_cart_page_activated' => true,
            'is_add_to_cart_popup_insurance_activated' => false,
            ];
        $this->configHelper->expects($this->once())->method('getConfigByCode')->willReturn($dbValue);
        $insuranceObject = $this->insuranceHelper->getConfig();
        $this->assertEquals($result, $insuranceObject->getArrayConfig());
    }

    /**
     * @dataProvider iframeHasDbGetParamsDataProvider
     * @return void
     */
    public function testIframeHasDbGetParams($dbValue, $expectedURL):void
    {
        $this->configHelper->expects($this->once())
            ->method('getConfigByCode')
            ->with(InsuranceHelper::ALMA_INSURANCE_CONFIG_CODE)
            ->willReturn($dbValue);
        $this->assertEquals($expectedURL, $this->insuranceHelper->getIframeUrlWithParams());
    }
    private function iframeHasDbGetParamsDataProvider():array
    {
        return [
            'No params if config is empty' => [
                'db_value' => '',
                'expectedUrl' => InsuranceHelper::IFRAME_BASE_URL
            ],
            'all params are true if all is true in DB' => [
                'db_value' => '{
                    "is_insurance_activated":true,
                    "is_insurance_on_product_page_activated":true,
                    "is_insurance_on_cart_page_activated":true,
                    "is_add_to_cart_popup_insurance_activated":true
                    }',
                'expectedUrl' => InsuranceHelper::IFRAME_BASE_URL.'?is_insurance_on_product_page_activated=true'.
                '&is_insurance_on_cart_page_activated=true'.
                '&is_add_to_cart_popup_insurance_activated=true'
            ],
            'all params are false if all is false in DB' => [
                'db_value' => '{
                    "is_insurance_activated":false,
                    "is_insurance_on_product_page_activated":false,
                    "is_insurance_on_cart_page_activated":false,
                    "is_add_to_cart_popup_insurance_activated":false
                    }',
                'expectedUrl' => InsuranceHelper::IFRAME_BASE_URL.'?is_insurance_on_product_page_activated=false'.
                    '&is_insurance_on_cart_page_activated=false'.
                    '&is_add_to_cart_popup_insurance_activated=false'
            ],
            'params are good values' => [
                'db_value' => '{
                    "is_insurance_activated":true,
                    "is_insurance_on_product_page_activated":false,
                    "is_insurance_on_cart_page_activated":true,
                    "is_add_to_cart_popup_insurance_activated":false
                    }',
                'expectedUrl' => InsuranceHelper::IFRAME_BASE_URL.'?is_insurance_on_product_page_activated=false'.
                    '&is_insurance_on_cart_page_activated=true'.
                    '&is_add_to_cart_popup_insurance_activated=false'
            ],
        ];
    }
    private function createNewInsuranceHelper():InsuranceHelper
    {
        return new InsuranceHelper(...$this->getConstructorDependency());
    }
}
