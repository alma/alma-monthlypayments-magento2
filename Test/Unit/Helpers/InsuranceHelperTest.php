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
use Alma\MonthlyPayments\Model\Insurance\SubscriptionFactory;
use Magento\Catalog\Api\Data\ProductInterface;
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
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\StoreManagerInterface;
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
    /**
     * @var \Alma\MonthlyPayments\Model\Insurance\SubscriptionFactory
     *
     */
    private $dbSubscriptionFactory;
    private $session;
    /**
     * @var StoreManagerInterface
     */
    private $storeManagerInterface;

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
        $this->dbSubscriptionFactory = $this->createMock(SubscriptionFactory::class);
        $subscriptionMock = $this->createPartialMock(\Alma\MonthlyPayments\Model\Insurance\Subscription::class, []);
        $this->dbSubscriptionFactory->method('create')->willReturn($subscriptionMock);
        $this->storeManagerInterface = $this->createMock(StoreManager::class);
        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturn('https://my-website.com');
        $this->storeManagerInterface->method('getStore')->willReturn($store);

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
            $this->dbSubscriptionFactory,
            $this->session,
            $this->storeManagerInterface
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
    public function testIframeHasDbGetParams($dbValue, $expectedURL, string $mode): void
    {
        $this->configHelper->expects($this->exactly(2))
            ->method('getConfigByCode')
            ->withConsecutive([InsuranceHelper::IS_ALLOWED_INSURANCE_PATH], [InsuranceHelper::ALMA_INSURANCE_CONFIG_CODE])
            ->willReturn($dbValue);
        $this->assertEquals($expectedURL, $this->insuranceHelper->getIframeUrlWithParams($mode));
    }

    public function testScriptUrlSandbox(): void
    {
        $this->assertEquals('https://protect.almapay.com/displayModal.js', $this->insuranceHelper->getScriptUrl('live'));
    }

    public function testScriptUrlLive(): void
    {
        $this->assertEquals('https://protect.sandbox.almapay.com/displayModal.js', $this->insuranceHelper->getScriptUrl('test'));
    }

    private function iframeHasDbGetParamsDataProvider(): array
    {
        return [
            'No params if config is empty' => [
                'db_value' => '',
                'expectedUrl' => 'https://protect.sandbox.almapay.com/almaBackOfficeConfiguration.html',
                'mode' => 'test'
            ],
            'all params are true if all is true in DB' => [
                'db_value' => '{
                    "is_insurance_activated":true,
                    "is_insurance_on_product_page_activated":true,
                    "is_insurance_on_cart_page_activated":true,
                    "is_add_to_cart_popup_insurance_activated":true
                    }',
                'expectedUrl' => 'https://protect.sandbox.almapay.com/almaBackOfficeConfiguration.html?is_insurance_on_product_page_activated=true' .
                    '&is_insurance_on_cart_page_activated=true' .
                    '&is_add_to_cart_popup_insurance_activated=true',
                'mode' => 'test'
            ],
            'all params are false if all is false in DB' => [
                'db_value' => '{
                    "is_insurance_activated":false,
                    "is_insurance_on_product_page_activated":false,
                    "is_insurance_on_cart_page_activated":false,
                    "is_add_to_cart_popup_insurance_activated":false
                    }',
                'expectedUrl' => 'https://protect.almapay.com/almaBackOfficeConfiguration.html?is_insurance_on_product_page_activated=false' .
                    '&is_insurance_on_cart_page_activated=false' .
                    '&is_add_to_cart_popup_insurance_activated=false',
                'mode' => 'live'
            ],
            'params are good values' => [
                'db_value' => '{
                    "is_insurance_activated":true,
                    "is_insurance_on_product_page_activated":false,
                    "is_insurance_on_cart_page_activated":true,
                    "is_add_to_cart_popup_insurance_activated":false
                    }',
                'expectedUrl' => 'https://protect.sandbox.almapay.com/almaBackOfficeConfiguration.html?is_insurance_on_product_page_activated=false' .
                    '&is_insurance_on_cart_page_activated=true' .
                    '&is_add_to_cart_popup_insurance_activated=false',
                'mode' => 'test'
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
    public function testHasInsuranceInRequest($insuranceId, $expected): void
    {
        $this->requestInterfaceMock->method('getParam')->willReturn($insuranceId);
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
        $item = $this->createMock(ProductInterface::class);
        $item->method('getName')->willReturn($parentName);
        $item->method('getPrice')->willReturn(53.00);
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
        $insuranceProductExpected = new InsuranceProduct($contract, $item);
        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint->method('getInsuranceContract')->willReturn($contract);
        $almaClient = $this->createMock(Client::class);
        $almaClient->insurance = $insuranceEndpoint;
        $this->almaClient->method('getDefaultClient')->willReturn($almaClient);
        $quoteId = '42';
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
        $collectionWithoutInsurance = $this->newCollectionFactory([$itemWithInsurance1, $itemInsurance, $itemWithoutInsurance2]);
        $subscription = $this->subscriptionFactory($subscriber);
        $subscriptionArray = $this->insuranceHelper->getSubscriptionData($collectionWithoutInsurance, $subscriber);
        $this->assertContainsOnlyInstancesOf(Subscription::class, $subscriptionArray);
        foreach ([$subscription] as $key => $subscription) {
            $this->assertEquals($subscription->getContractId(), $subscriptionArray[$key]->getContractId());
            $this->assertEquals($subscription->getCmsReference(), $subscriptionArray[$key]->getCmsReference());
            $this->assertEquals($subscription->getProductPrice(), $subscriptionArray[$key]->getProductPrice());
            $this->assertEquals($subscription->getSubscriber(), $subscriptionArray[$key]->getSubscriber());
            $this->assertTrue(boolval($subscriptionArray[$key]->getSubscriber()));
        }
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

    public function testCreateDbSubscriptionWithItemCollectionAndSubscriptionApiResult(): void
    {
        $itemWithoutInsurance2 = $this->invoiceItemFactory('mySku2');
        $itemWithInsurance1 = $this->invoiceItemFactory('mySku', true, 2, 22);
        $itemWithInsurance2 = $this->invoiceItemFactory('mySku', true, 2, 24);
        $itemInsurance = $this->invoiceItemFactory(InsuranceHelper::ALMA_INSURANCE_SKU, true, 2, 23, 'insurance_contract_5LH0o7qj87xGp6sF1AGWqx', '12300', 5900);
        $itemInsurance2 = $this->invoiceItemFactory(InsuranceHelper::ALMA_INSURANCE_SKU, true, 2, 25, 'insurance_contract_5LH0o7qj87xGp6sF1AGWqx', '12300', 5600);
        $collectionWithInsurance = $this->newCollectionFactory([$itemWithInsurance1, $itemInsurance, $itemWithoutInsurance2, $itemWithInsurance2, $itemInsurance2]);

        $subscriptionResult = '{
        "subscriptions":
            [
                {
                "contract_id":"insurance_contract_5LH0o7qj87xGp6sF1AGWqx",
                "id":"subscription_298QYLM3q94luQSD34LDlr",
                "amount":"12312",
                "broker_subscription_id":"provider_subscription_298QYLM3q94luQSD34LDlr",
                "cms_reference":"24-MB02",
                "state":"started"
                },
                {
                "contract_id":"insurance_contract_5LH0o7qj87xGp6sF1AGWqx",
                "id":"subscription_2333333333333333333333",
                "amount":"12456",
                "broker_subscription_id":"provider_subscription_2333333333333333333333",
                "cms_reference":"24-MB02",
                "state":"started"
                }
            ]
        }';
        $mode = 'test';
        $expected = [
            [
                'order_id' => 2,
                'order_item_id' => 23,
                'name' => 'Alma outillage thermique 3 ans (Vol + casse)',
                'subscription_id' => 'subscription_298QYLM3q94luQSD34LDlr',
                'subscription_broker_id' => 'provider_subscription_298QYLM3q94luQSD34LDlr',
                'subscription_amount' => 12312,
                'contract_id' => 'insurance_contract_5LH0o7qj87xGp6sF1AGWqx',
                'cms_reference' => '24-MB02',
                'linked_product_name' => 'Fusion Backpack',
                'linked_product_price' => 5900,
                'subscription_state' => 'started',
                'mode' => 'test',
                'callback_url' => 'https://my-website.com/rest/V1/alma/insurance/update?subscription_id=<subscription_id>&trace=<trace>',
            ],
            [
                'order_id' => 2,
                'order_item_id' => 25,
                'name' => 'Alma outillage thermique 3 ans (Vol + casse)',
                'subscription_id' => 'subscription_2333333333333333333333',
                'subscription_broker_id' => 'provider_subscription_2333333333333333333333',
                'subscription_amount' => 12456,
                'contract_id' => 'insurance_contract_5LH0o7qj87xGp6sF1AGWqx',
                'cms_reference' => '24-MB02',
                'linked_product_name' => 'Fusion Backpack',
                'linked_product_price' => 5600,
                'subscription_state' => 'started',
                'mode' => 'test',
                'callback_url' => 'https://my-website.com/rest/V1/alma/insurance/update?subscription_id=<subscription_id>&trace=<trace>',
            ],
        ];
        $arraySubscriptionResult = $this->insuranceHelper->createDbSubscriptionArrayFromItemsAndApiResult(
            $collectionWithInsurance,
            json_decode($subscriptionResult, true)['subscriptions'],
            $mode
        );

        foreach ($arraySubscriptionResult as $key => $subscription) {
            $this->assertEquals($expected[$key], $subscription->getData());
        }
    }

    private function subscriptionFactory(Subscriber $subscriber, string $contractId = 'contract_id_123', string $sku = 'mySku', int $amount =11): Subscription
    {
        return new Subscription(
            $contractId,
            $amount,
            $sku,
            12012,
            $subscriber,
            'https://my-website.com/rest/V1/alma/insurance/update'
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
    private function invoiceItemFactory(string $sku, bool $hasInsuranceData = false, int $orderId = 1, int $orderItemId = 1, string $contractId = 'contract_id_123', string $price = '11', int $parentPrice = 5300): InvoiceItem
    {
        $invoiceItem = $this->createMock(InvoiceItem::class);
        $orderItem = $this->createMock(OrderItem::class);
        if ($hasInsuranceData) {
            $orderItem->method('getData')->willReturn($this->getInsuranceData('1234', $contractId, $price, $parentPrice));
            $orderItem->method('getOriginalPrice')->willReturn(120.12);
            $orderItem->method('getOrderId')->willReturn($orderId);
            $orderItem->method('getItemId')->willReturn($orderItemId);
            $orderItem->method('getSku')->willReturn($sku);
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

    private function getInsuranceData(string $linkToken = null, string $contractId = 'contract_id_123', string $price = '11', int $parent_price = 5300): ?string
    {
        if (!$linkToken) {
            return null;
        }
        return '{"id":"' . $contractId . '","name":"Alma outillage thermique 3 ans (Vol + casse)","price":' . $price . ',"link":"' . $linkToken . '","parent_name":"Fusion Backpack", "parent_price":"'.$parent_price .'"}';
    }

    private function getInsuranceDataWithType(string $type, string $linkToken = null): ?string
    {
        if (!$linkToken) {
            return null;
        }
        return '{"id":"contract_id_123","name":"Alma outillage thermique 3 ans (Vol + casse)","price":11,"link":"' . $linkToken . '","parent_name":"Fusion Backpack","type":"' . $type . '", "parent_price": "5300"}';
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
