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
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
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
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->requestInterfaceMock = $this->createMock(RequestInterface::class);
        $this->productRepositoryMock = $this->createMock(ProductRepository::class);
        $this->logger = $this->createMock(Logger::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
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
            $this->configHelper,
            $this->cartRepository
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
    public function testConfigObjectIsCreatedWithDbData($activated, $pageActivated, $cartActivated, $popupActivated, $dbValue):void
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
                'expectedUrl' => InsuranceHelper::CONFIG_IFRAME_URL
            ],
            'all params are true if all is true in DB' => [
                'db_value' => '{
                    "is_insurance_activated":true,
                    "is_insurance_on_product_page_activated":true,
                    "is_insurance_on_cart_page_activated":true,
                    "is_add_to_cart_popup_insurance_activated":true
                    }',
                'expectedUrl' => InsuranceHelper::CONFIG_IFRAME_URL . '?is_insurance_on_product_page_activated=true' .
                '&is_insurance_on_cart_page_activated=true' .
                '&is_add_to_cart_popup_insurance_activated=true'
            ],
            'all params are false if all is false in DB' => [
                'db_value' => '{
                    "is_insurance_activated":false,
                    "is_insurance_on_product_page_activated":false,
                    "is_insurance_on_cart_page_activated":false,
                    "is_add_to_cart_popup_insurance_activated":false
                    }',
                'expectedUrl' => InsuranceHelper::CONFIG_IFRAME_URL . '?is_insurance_on_product_page_activated=false' .
                    '&is_insurance_on_cart_page_activated=false' .
                    '&is_add_to_cart_popup_insurance_activated=false'
            ],
            'params are good values' => [
                'db_value' => '{
                    "is_insurance_activated":true,
                    "is_insurance_on_product_page_activated":false,
                    "is_insurance_on_cart_page_activated":true,
                    "is_add_to_cart_popup_insurance_activated":false
                    }',
                'expectedUrl' => InsuranceHelper::CONFIG_IFRAME_URL . '?is_insurance_on_product_page_activated=false' .
                    '&is_insurance_on_cart_page_activated=true' .
                    '&is_add_to_cart_popup_insurance_activated=false'
            ],
        ];
    }
    public function testCartWithoutInsuranceDontChange():void
    {
        $result = [
            [
                'name'=> 'Product1',
                'isInsuranceProduct' => false
            ],
            [
                'name'=> 'Product2',
                'isInsuranceProduct' => false
            ],
            [
                'name'=> 'Product3',
                'isInsuranceProduct' => false
            ],
        ];
        $this->assertEquals($result, $this->insuranceHelper->reorderMiniCart($result));
    }
    public function testCartWithInsuranceProductChangePosition():void
    {
        $base = [
            [
                'name'=> 'Product1',
                'isInsuranceProduct' => false
            ],
            [
                'name'=> 'Alma insurance1',
                'isInsuranceProduct' => true
            ],
            [
                'name'=> 'Product3',
                'isInsuranceProduct' => false
            ],
            [
                'name'=> 'Alma insurance2',
                'isInsuranceProduct' => true
            ],
            [
                'name'=> 'Product4',
                'isInsuranceProduct' => false
            ],
        ];
        $result = [
            [
                'name'=> 'Product1',
                'isInsuranceProduct' => false
            ],
            [
                'name'=> 'Product3',
                'isInsuranceProduct' => false
            ],
            [
                'name'=> 'Alma insurance1',
                'isInsuranceProduct' => true
            ],
            [
                'name'=> 'Product4',
                'isInsuranceProduct' => false
            ],
            [
                'name'=> 'Alma insurance2',
                'isInsuranceProduct' => true
            ]
        ];
        $this->assertEquals($result, $this->insuranceHelper->reorderMiniCart($base));
    }
    public function testReturnNullWithEMptyQuoteItem()
    {
        $linkToken = 'toremove';
        $quoteItems = [
        ];
        $result = $this->insuranceHelper->getInsuranceProductToRemove($linkToken, $quoteItems);
        $this->assertNull($result);
    }
    public function testIfNoInsuranceProductToRemoveReturnNull()
    {
        $linkToken = 'toremove';
        $insuranceToKeep = $this->itemFactory(InsuranceHelper::ALMA_INSURANCE_SKU, 'tokeep');
        $productWithInsurance = $this->itemFactory('SKU1', 'toremove');
        $productWithoutInsurance = $this->itemFactory('SKU2');
        $quoteItems = [
            $productWithInsurance,
            $insuranceToKeep,
            $productWithoutInsurance,
        ];
        $result = $this->insuranceHelper->getInsuranceProductToRemove($linkToken, $quoteItems);
        $this->assertNull($result);
    }
    public function testGetInsuranceProductWithALinkToken()
    {
        $linkToken = 'toremove';
        $insuranceToKeep = $this->itemFactory(InsuranceHelper::ALMA_INSURANCE_SKU, 'tokeep');
        $insuranceToRemove = $this->itemFactory(InsuranceHelper::ALMA_INSURANCE_SKU, 'toremove');
        $productWithInsurance = $this->itemFactory('SKU1', 'toremove');
        $productWithoutInsurance = $this->itemFactory('SKU2');
        $quoteItems = [
            $productWithInsurance,
            $insuranceToKeep,
            $productWithoutInsurance,
            $insuranceToRemove
        ];
        $result = $this->insuranceHelper->getInsuranceProductToRemove($linkToken, $quoteItems);
        $this->assertSame($insuranceToRemove, $result);
    }
	public function testGetProductWithInsuranceWithALinkToken()
	{
		$linkToken = 'linkproduct';
		$insuranceProduct = $this->itemFactory(InsuranceHelper::ALMA_INSURANCE_SKU, 'insurance_product');
		$insurance = $this->itemFactory(InsuranceHelper::ALMA_INSURANCE_SKU, 'linkproduct');
		$productWithInsurance = $this->itemFactory('SKU1', 'linkproduct');
		$productWithoutInsurance = $this->itemFactory('SKU2');
		$quoteItems = [
			$productWithoutInsurance,
			$insurance,
			$insuranceProduct,
			$productWithInsurance
		];
		$result = $this->insuranceHelper->getProductLinkedToInsurance($linkToken, $quoteItems);
		$this->assertSame($productWithInsurance, $result);
	}

	public function testgetProductReturnNullWithEMptyQuoteItem()
	{
		$linkToken = 'toremove';
		$quoteItems = [
		];
		$result = $this->insuranceHelper->getProductLinkedToInsurance($linkToken, $quoteItems);
		$this->assertNull($result);
	}
    public function testRemoveMustCallDeleteItemAndSave():void
    {
        $quoteMock = $this->createMock(Quote::class);
        $itemToRemove = $this->createMock(Item::class);
        $itemToRemove->method('getQuote')->willReturn($quoteMock);
        $quoteMock->expects($this->once())->method('deleteItem')->with($itemToRemove);

        $this->cartRepository->expects($this->once())->method('save')->with($quoteMock);
        $this->assertNull($this->insuranceHelper->removeQuoteItemFromCart($itemToRemove));
    }


    private function getInsuranceData(string $linkToken = null):?string
    {
        if (!$linkToken) {
            return null;
        }
        return '{"id":1,"name":"Casse","price":11,"link":"' . $linkToken . '","parent_name":"Fusion Backpack"}';
    }

    private function itemFactory(string $sku, string $linkToken = null):Item
    {
        $item = $this->createMock(Item::class);
        $item->method('getSku')->willReturn($sku);
        $item->method('getData')->willReturn($this->getInsuranceData($linkToken));
        return $item;
    }

    private function createNewInsuranceHelper():InsuranceHelper
    {
        return new InsuranceHelper(...$this->getConstructorDependency());
    }
}
