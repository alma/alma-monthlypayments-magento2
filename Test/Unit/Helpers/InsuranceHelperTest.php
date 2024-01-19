<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Client;
use Alma\API\Endpoints\Insurance;
use Alma\API\Entities\Insurance\Contract;
use Alma\API\Entities\Insurance\Subscriber;
use Alma\API\Entities\Insurance\Subscription;
use Alma\API\Exceptions\AlmaException;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Data\InsuranceConfig;
use Alma\MonthlyPayments\Model\Data\InsuranceProduct;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Item\Collection;
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
    /**
     * @var AlmaClient
     *
     */
    private $almaClient;
    private $session;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->requestInterfaceMock = $this->createMock(RequestInterface::class);
        $this->productRepositoryMock = $this->createMock(ProductRepository::class);
        $this->logger = $this->createMock(Logger::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->session = $this->createMock(Session::class);
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
            $this->cartRepository,
            $this->almaClient,
            $this->session
        ];
    }

    //
    public function testInsuranceConfigIsAnInsuranceConfigObject(): void
    {
        $this->configHelper->expects($this->exactly(2))->method('getConfigByCode')->willReturn('');
        $this->assertTrue(get_class($this->insuranceHelper->getConfig()) == InsuranceConfig::class);
    }

    public function testInsuranceConfigGetDataInDb(): void
    {
        $this->configHelper->expects($this->exactly(2))
            ->method('getConfigByCode')
            ->withConsecutive([InsuranceHelper::IS_ALLOWED_INSURANCE_PATH], [InsuranceHelper::ALMA_INSURANCE_CONFIG_CODE])
            ->willReturn('');
        $this->insuranceHelper->getConfig();
    }

    /**
     * @param $result
     * @param $dbValue
     * @return void
     * @dataProvider configObjectIsCreatedWithDbDataDataProvider
     */
    public function testConfigObjectIsCreatedWithDbData($activated, $pageActivated, $cartActivated, $popupActivated, $isAllowed, $dbValue): void
    {
        $this->configHelper->expects($this->exactly(2))->method('getConfigByCode')->willReturnOnConsecutiveCalls($isAllowed, $dbValue);
        $insuranceObject = $this->insuranceHelper->getConfig();
        $this->assertEquals($activated, $insuranceObject->isActivated());
        $this->assertEquals($pageActivated, $insuranceObject->isPageActivated());
        $this->assertEquals($cartActivated, $insuranceObject->isCartActivated());
        $this->assertEquals($popupActivated, $insuranceObject->isPopupActivated());
        $this->assertEquals((bool)$isAllowed, $insuranceObject->isAllowed());
    }

    private function configObjectIsCreatedWithDbDataDataProvider(): array
    {
        return [
            'Return false if no data in DB' => [
                'activated' => false,
                'page_activated' => false,
                'cart_activated' => false,
                'popup_activated' => false,
                'is_allowed' => '0',
                'db_value' => ''
            ],
            'Return false if null in DB' => [
                'activated' => false,
                'page_activated' => false,
                'cart_activated' => false,
                'popup_activated' => false,
                'is_allowed' => '0',
                'db_value' => null
            ],
            'Return false if key is not present and ignore unknown keys ' => [
                'activated' => false,
                'page_activated' => false,
                'cart_activated' => false,
                'popup_activated' => true,
                'is_allowed' => '0',
                'db_value' => '{
                    "is_activated":true,
                    "is_on_product_page_activated":true,
                    "is_on_cart_page_activated":true,
                    "is_add_to_cart_popup_insurance_activated":true
                    }'
            ],
            'Return true all is true in DB' => [
                'activated' => true,
                'page_activated' => true,
                'cart_activated' => true,
                'popup_activated' => true,
                'is_allowed' => '0',
                'db_value' => '{
                    "is_insurance_activated":true,
                    "is_insurance_on_product_page_activated":true,
                    "is_insurance_on_cart_page_activated":true,
                    "is_add_to_cart_popup_insurance_activated":true
                    }'
            ],
            'Return false if all is false in DB' => [
                'activated' => false,
                'page_activated' => false,
                'cart_activated' => false,
                'popup_activated' => false,
                'is_allowed' => '0',
                'db_value' => '{
                    "is_insurance_activated":false,
                    "is_insurance_on_product_page_activated":false,
                    "is_insurance_on_cart_page_activated":false,
                    "is_add_to_cart_popup_insurance_activated":false
                    }'
            ],
            'Return good values' => [
                'activated' => true,
                'page_activated' => false,
                'cart_activated' => true,
                'popup_activated' => false,
                'is_allowed' => '0',
                'db_value' => '{
                    "is_insurance_activated":true,
                    "is_insurance_on_product_page_activated":false,
                    "is_insurance_on_cart_page_activated":true,
                    "is_add_to_cart_popup_insurance_activated":false
                    }'
            ]
        ];
    }

    public function testReturnArrayValueInDb(): void
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
        $this->configHelper->expects($this->exactly(2))->method('getConfigByCode')->willReturn($dbValue);
        $insuranceObject = $this->insuranceHelper->getConfig();
        $this->assertEquals($result, $insuranceObject->getArrayConfig());
    }

    /**
     * @dataProvider iframeHasDbGetParamsDataProvider
     * @return void
     */
    public function testIframeHasDbGetParams($dbValue, $expectedURL): void
    {
        $this->configHelper->expects($this->exactly(2))
            ->method('getConfigByCode')
            ->withConsecutive([InsuranceHelper::IS_ALLOWED_INSURANCE_PATH], [InsuranceHelper::ALMA_INSURANCE_CONFIG_CODE])
            ->willReturn($dbValue);
        $this->assertEquals($expectedURL, $this->insuranceHelper->getIframeUrlWithParams());
    }

    private function iframeHasDbGetParamsDataProvider(): array
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

    public function testCartWithoutInsuranceDontChange(): void
    {
        $result = [
            [
                'name' => 'Product1',
                'isInsuranceProduct' => false
            ],
            [
                'name' => 'Product2',
                'isInsuranceProduct' => false
            ],
            [
                'name' => 'Product3',
                'isInsuranceProduct' => false
            ],
        ];
        $this->assertEquals($result, $this->insuranceHelper->reorderMiniCart($result));
    }

    public function testCartWithInsuranceProductChangePosition(): void
    {
        $base = [
            [
                'name' => 'Product1',
                'isInsuranceProduct' => false
            ],
            [
                'name' => 'Alma insurance1',
                'isInsuranceProduct' => true
            ],
            [
                'name' => 'Product3',
                'isInsuranceProduct' => false
            ],
            [
                'name' => 'Alma insurance2',
                'isInsuranceProduct' => true
            ],
            [
                'name' => 'Product4',
                'isInsuranceProduct' => false
            ],
        ];
        $result = [
            [
                'name' => 'Product1',
                'isInsuranceProduct' => false
            ],
            [
                'name' => 'Product3',
                'isInsuranceProduct' => false
            ],
            [
                'name' => 'Alma insurance1',
                'isInsuranceProduct' => true
            ],
            [
                'name' => 'Product4',
                'isInsuranceProduct' => false
            ],
            [
                'name' => 'Alma insurance2',
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
        $insuranceToKeep = $this->quoteItemFactory(InsuranceHelper::ALMA_INSURANCE_SKU, 'tokeep');
        $productWithInsurance = $this->quoteItemFactory('SKU1', 'toremove');
        $productWithoutInsurance = $this->quoteItemFactory('SKU2');
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
        $insuranceToKeep = $this->quoteItemFactory(InsuranceHelper::ALMA_INSURANCE_SKU, 'tokeep');
        $insuranceToRemove = $this->quoteItemFactory(InsuranceHelper::ALMA_INSURANCE_SKU, 'toremove');
        $productWithInsurance = $this->quoteItemFactory('SKU1', 'toremove');
        $productWithoutInsurance = $this->quoteItemFactory('SKU2');
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
        $insuranceProduct = $this->quoteItemFactory(InsuranceHelper::ALMA_INSURANCE_SKU, 'insurance_product');
        $insurance = $this->quoteItemFactory(InsuranceHelper::ALMA_INSURANCE_SKU, 'linkproduct');
        $productWithInsurance = $this->quoteItemFactory('SKU1', 'linkproduct');
        $productWithoutInsurance = $this->quoteItemFactory('SKU2');
        $quoteItems = [
            $productWithoutInsurance,
            $insurance,
            $insuranceProduct,
            $productWithInsurance
        ];
        $result = $this->insuranceHelper->getProductLinkedToInsurance($linkToken, $quoteItems);
        $this->assertSame($productWithInsurance, $result);
    }

    public function testGetProductReturnNullWithEmptyQuoteItem()
    {
        $linkToken = 'toremove';
        $quoteItems = [];
        $result = $this->insuranceHelper->getProductLinkedToInsurance($linkToken, $quoteItems);
        $this->assertNull($result);
    }

    public function testRemoveMustCallDeleteItemAndSave(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $itemToRemove = $this->createMock(Item::class);
        $itemToRemove->method('getQuote')->willReturn($quoteMock);
        $quoteMock->expects($this->once())->method('deleteItem')->with($itemToRemove);

        $this->cartRepository->expects($this->once())->method('save')->with($quoteMock);
        $this->assertNull($this->insuranceHelper->removeQuoteItemFromCart($itemToRemove));
    }

    /**
     * @dataProvider dataForSetAlmaInsurance
     * @return void
     */
    public function testSetDataToAlmaInsurance($data, $type, $result): void
    {
        $quoteItem = $this->createMock(Item::class);
        $this->jsonMock->expects($this->once())->method('serialize')->with(json_decode($result, true))->willReturn($result);
        $quoteItem->expects($this->once())->method('setData')->with(InsuranceHelper::ALMA_INSURANCE_SKU, $result)->willReturn($quoteItem);
        $this->insuranceHelper->setAlmaInsuranceToQuoteItem($quoteItem, $data, $type);
    }

    public function dataForSetAlmaInsurance(): array
    {
        return [
            'Default type returned for no type added' => [
                'data' => json_decode($this->getInsuranceData('123456'), true),
                'type' => null,
                'result' => $this->getInsuranceDataWithType(InsuranceHelper::ALMA_INSURANCE_SKU, '123456')
            ],
            'Alma insurance type returned for no alma insurance type added' => [
                'data' => json_decode($this->getInsuranceData('123456'), true),
                'type' => InsuranceHelper::ALMA_INSURANCE_SKU,
                'result' => $this->getInsuranceDataWithType(InsuranceHelper::ALMA_INSURANCE_SKU, '123456')
            ],
            'Product with insurance type returned for no alma insurance type added' => [
                'data' => json_decode($this->getInsuranceData('123456'), true),
                'type' => InsuranceHelper::ALMA_PRODUCT_WITH_INSURANCE_TYPE,
                'result' => $this->getInsuranceDataWithType(InsuranceHelper::ALMA_PRODUCT_WITH_INSURANCE_TYPE, '123456')
            ],
        ];
    }

    public function testGetInsuranceName(): void
    {
        $quoteItem = $this->createMock(Item::class);
        $quoteItem->method('getData')->willReturn($this->getInsuranceData('abcd'));
        $this->assertEquals('Alma outillage thermique 3 ans (Vol + casse)', $this->insuranceHelper->getInsuranceName($quoteItem));
    }

    /**
     * @dataProvider insuranceInRequest
     * @return void
     */
    public function testHasInsuranceInRequest($insurandId, $expected): void
    {
        $this->requestInterfaceMock->method('getParam')->willReturn($insurandId);
        $this->assertEquals($expected, $this->insuranceHelper->hasInsuranceInRequest());
    }

    public function testGetInsuranceProductReturnNullIfPhpClientThrowException(): void
    {
        $insuranceId = 'alm_insurance_id123456789';
        $item = $this->createMock(Product::class);

        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint->method('getInsuranceContract')->willThrowException(new AlmaException());
        $almaClient = $this->createMock(Client::class);
        $almaClient->insurance = $insuranceEndpoint;
        $this->almaClient->method('getDefaultClient')->willReturn($almaClient);
        $this->assertNull($this->insuranceHelper->getInsuranceProduct($item, $insuranceId));
    }

    public function testGetInsuranceProductReturnInsuranceProduct(): void
    {
        $insuranceId = 'alm_insurance_id123456789';
        $parentName = 'fusion back pack';
        $item = $this->createMock(Product::class);
        $item->method('getName')->willReturn($parentName);
        $contract = new Contract(
            "alm_insurance_id123456789",
            "Alma outillage thermique 3 ans (Vol + casse)",
            365,
            null,
            null,
            null,
            null,
            null,
            500,
            []
        );
        $insuranceProductExpected = new InsuranceProduct($contract, $parentName);
        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint->method('getInsuranceContract')->willReturn($contract);
        $almaClient = $this->createMock(Client::class);
        $almaClient->insurance = $insuranceEndpoint;
        $this->almaClient->method('getDefaultClient')->willReturn($almaClient);
        $quoteId  = '42';
        $this->assertEquals($insuranceProductExpected, $this->insuranceHelper->getInsuranceProduct($item, $insuranceId, $quoteId));
    }

    public function testGetSubscriptionDataReturnMustBeAnArray(): void
    {
        $itemWithoutInsurance1 = $this->invoiceItemFactory('mySku1');
        $itemWithoutInsurance2 = $this->invoiceItemFactory('mySku2');
        $collectionWithoutInsurance = $this->newCollectionFactory([$itemWithoutInsurance1, $itemWithoutInsurance2]);
        $this->assertIsArray($this->insuranceHelper->getSubscriptionData($collectionWithoutInsurance, $this->subscriberFactory()));
    }

    public function testForCollectionWithoutProductWithInsuranceReturnMustBeAnEmptyArray(): void
    {
        $itemWithoutInsurance1 = $this->invoiceItemFactory('mySku1');
        $itemWithoutInsurance2 = $this->invoiceItemFactory('mySku2');
        $collectionWithoutInsurance = $this->newCollectionFactory([$itemWithoutInsurance1, $itemWithoutInsurance2]);
        $this->assertEmpty($this->insuranceHelper->getSubscriptionData($collectionWithoutInsurance, $this->subscriberFactory()));
    }

    public function testForCollectionProductWithInsuranceReturnSubscriptionArray(): void
    {
        $subscriber = $this->subscriberFactory();
        $itemWithInsurance1 = $this->invoiceItemFactory('mySku', true);
        $itemWithoutInsurance2 = $this->invoiceItemFactory('mySku2');
        $itemInsurance = $this->invoiceItemFactory(InsuranceHelper::ALMA_INSURANCE_SKU, true);
        $collectionWithoutInsurance = $this->newCollectionFactory([$itemWithInsurance1,$itemInsurance, $itemWithoutInsurance2]);
        $subscription = $this->subscriptionFactory($subscriber);
        $subscriptionArray = $this->insuranceHelper->getSubscriptionData($collectionWithoutInsurance, $subscriber);
        $this->assertContainsOnlyInstancesOf(Subscription::class, $subscriptionArray);
        $this->assertEquals([$subscription], $subscriptionArray);
    }

    public function testGetSubscriberWithInvoice(): void
    {
        $subscriber = $this->subscriberFactory();
        $billingAddress = $this->createMock(Address::class);
        $billingAddress->method('getEmail')->willReturn('test@almapay.com');
        $billingAddress->method('getTelephone')->willReturn('0601020304');
        $billingAddress->method('getFirstname')->willReturn('Doe');
        $billingAddress->method('getLastname')->willReturn('John');
        $billingAddress->method('getStreet')->willReturn(['Rue des petites ecuries']);
        $billingAddress->method('getPostcode')->willReturn('75010');
        $billingAddress->method('getCity')->willReturn('Paris');
        $billingAddress->method('getCountryId')->willReturn('FR');
        $this->assertEquals($subscriber, $this->insuranceHelper->getSubscriberByAddress($billingAddress));
    }

    private function subscriptionFactory(Subscriber $subscriber): Subscription
    {
        return new Subscription(
            'contract_id_123',
            'mySku',
            '12012',
            $subscriber
        );
    }

    private function newCollectionFactory(array $items): Collection
    {
        $itemsCollection = $this->createMock(Collection::class);
        $iterator = new \ArrayIterator($items);
        $itemsCollection->method('getIterator')->willReturn($iterator);
        return $itemsCollection;
    }

    /**
     * @return InvoiceItem
     */
    private function invoiceItemFactory(string $sku, bool $hasInsuranceData = false): InvoiceItem
    {
        $invoiceItem = $this->createMock(InvoiceItem::class);
        $orderItem = $this->createMock(OrderItem::class);
        if ($hasInsuranceData) {
            $orderItem->method('getData')->willReturn($this->getInsuranceData('1234'));
            $orderItem->method('getOriginalPrice')->willReturn(120.12);
        }
        $invoiceItem->method('getSku')->willReturn($sku);
        $invoiceItem->method('getOrderItem')->willReturn($orderItem);
        return $invoiceItem;
    }

    private function subscriberFactory(): Subscriber
    {
        return new Subscriber(
            'test@almapay.com',
            '0601020304',
            'John',
            'Doe',
            'Rue des petites ecuries',
            '',
            '75010',
            'Paris',
            'FR',
            null
        );
    }

    public function insuranceInRequest(): array
    {
        return [
            'No insurance in request' => [
                'insurance_id' => 'insurance_id_23456789',
                'expected' => true
            ],
            'Null insurance in request' => [
                'insurance_id' => null,
                'expected' => false
            ],
            'Insurance in request' => [
                'insurance_id' => '',
                'expected' => false
            ]
        ];
    }

    private function getInsuranceData(string $linkToken = null): ?string
    {
        if (!$linkToken) {
            return null;
        }
        return '{"id":"contract_id_123","name":"Alma outillage thermique 3 ans (Vol + casse)","price":11,"link":"' . $linkToken . '","parent_name":"Fusion Backpack"}';
    }

    private function getInsuranceDataWithType(string $type, string $linkToken = null): ?string
    {
        if (!$linkToken) {
            return null;
        }
        return '{"id":"contract_id_123","name":"Alma outillage thermique 3 ans (Vol + casse)","price":11,"link":"' . $linkToken . '","parent_name":"Fusion Backpack","type":"' . $type . '"}';
    }


    private function quoteItemFactory(string $sku, string $linkToken = null): Item
    {
        $item = $this->createMock(Item::class);
        $item->method('getSku')->willReturn($sku);
        $item->method('getData')->willReturn($this->getInsuranceData($linkToken));
        return $item;
    }


    private function createNewInsuranceHelper(): InsuranceHelper
    {
        return new InsuranceHelper(...$this->getConstructorDependency());
    }
}
