<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\OrderHelper;
use Alma\MonthlyPayments\Helpers\ProductHelper;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollectionAlias;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class OrderHelperTest extends TestCase
{
    const MEDIA_BASE_URL = 'https://adobe-commerce-a-2-4-5.local.test/media/';
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var OrderFactory
     */
    private $orderFactory;
    private $logger;
    /**
     * @var Context
     */
    private $contextMock;
    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    private $productHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManagerInterface;


    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->orderFactory = $this->createMock(OrderFactory::class);
        $this->orderCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->orderManagement = $this->createMock(OrderManagementInterface::class);
        $this->productHelper = $this->createMock(ProductHelper::class);
        $this->logger = $this->createMock(Logger::class);

        $storeInterface = $this->createMock(Store::class);
        $storeInterface->method('getBaseUrl')->willReturn(self::MEDIA_BASE_URL);

        $this->storeManagerInterface = $this->createMock(StoreManagerInterface::class);
        $this->storeManagerInterface->method('getStore')->willReturn($storeInterface);
    }

    private function getConstructorDependency(): array
    {
        return [
            $this->contextMock,
            $this->orderFactory,
            $this->orderCollectionFactory,
            $this->orderRepository,
            $this->orderManagement,
            $this->productHelper,
            $this->storeManagerInterface,
            $this->logger
        ];
    }

    private function createNewOrderHelper(): OrderHelper
    {
        return new OrderHelper(...$this->getConstructorDependency());
    }

    public function testInstanceConfigHelper()
    {
        $orderHelper = $this->createNewOrderHelper();
        $this->assertInstanceOf(OrderHelper::class, $orderHelper);
    }

    public function testImplementAbstractHelper()
    {
        $orderHelper = $this->createNewOrderHelper();
        $this->assertInstanceOf(AbstractHelper::class, $orderHelper);
    }

    public function testCancelOrderUseOrderId()
    {
        $mockOrderId = 10;
        $this->orderManagement->expects($this->once())
            ->method('cancel')
            ->with($mockOrderId)
            ->willReturn(true);
        $orderHelper = $this->createNewOrderHelper();
        $orderHelper->cancel($mockOrderId);
    }

    public function testNotifyOrderUseOrderId()
    {
        $mockOrderId = 10;
        $this->orderManagement->expects($this->once())
            ->method('notify')
            ->with($mockOrderId);
        $orderHelper = $this->createNewOrderHelper();
        $orderHelper->notify($mockOrderId);
    }

    public function testSaveMethodStructure(): void
    {
        $mockOrder = $this->createMock(Order::class);
        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($mockOrder);
        $orderHelper = $this->createNewOrderHelper();
        $orderHelper->save($mockOrder);
    }

    /**
     * @dataProvider CartPayloadDataProvider
     *
     * @return void
     */
    public function testBuild(array $dataProducts, array $dataItems, $expectedPayload): void
    {
        $products = [];
        $items = [];
        foreach ($dataProducts as $dataProduct) {
            $products[] = $this->productFactory(...$dataProduct);
        }
        foreach ($dataItems as $dataItem) {
            $items[] = $this->itemFactory(...$dataItem);
        }

        $productIterator = new \ArrayIterator($products);
        $productCollectionMock = $this->createMock(ProductCollectionAlias::class);
        $productCollectionMock->method('getIterator')->willReturn($productIterator);

        $catGear = $this->createMock(Category::class);
        $catGear->method('getEntityId')->willReturn('3');
        $catGear->method('getName')->willReturn('Gear');
        $catBag = $this->createMock(Category::class);
        $catBag->method('getEntityId')->willReturn('4');
        $catBag->method('getName')->willReturn('Bags');
        $categoriesCollectionMock = $this->createMock(Collection::class);
        $categoryIterator = new \ArrayIterator([$catGear, $catBag]);
        $categoriesCollectionMock->method('getIterator')->willReturn($categoryIterator);

        $this->productHelper = $this->createMock(ProductHelper::class);
        $this->productHelper->method('getProductsItems')->willReturn($productCollectionMock);
        $this->productHelper->method('getProductsCategories')->willReturn($categoriesCollectionMock);

        $cartDataBuilder = $this->createNewOrderHelper();
        $this->assertEquals(
            $expectedPayload,
            $cartDataBuilder->formatOrderItems(
                $items
            )
        );
    }

    public function cartPayloadDataProvider(): array
    {
        $simple1 = [
            '1',
            'base-01',
            'Simple product 1',
            2.0,
            22.30,
            44.60,
            0,
            2.10
        ];
        $formattedSimple1 = $this->formattedItemFactory(
            'base-01',
            'Simple product 1',
            '',
            2,
            2230,
            4460,
            false,
            true
        );
        $simple2 = [
            '2',
            'base-02',
            'Simple product 2',
            1.0,
            24.30,
            24.30,
            0,
            1.10
        ];
        $formattedSimple2 = $this->formattedItemFactory(
            'base-02',
            'Simple product 2',
            '',
            1,
            2430,
            2430,
            false,
            true
        );
        $formattedSimple2WithoutImage = $this->formattedItemFactory(
            'base-02',
            'Simple product 2',
            '',
            1,
            2430,
            2430,
            false,
            true,
            ['Gear', 'Bags'],
            ''
        );
        $simpleWithoutCategory = [
            '3',
            'base-02',
            'Simple product 2',
            1.0,
            24.30,
            24.30,
            0,
            1.10,
        ];
        $formattedSimpleWithoutCategory = $this->formattedItemFactory(
            'base-02',
            'Simple product 2',
            '',
            1,
            2430,
            2430,
            false,
            true,
            []
        );
        $virtualProduct1 = [
            '4',
            'base-02',
            'virtual product 4',
            1.0,
            24.30,
            24.30,
            1,
            1.10
        ];
        $formattedVirtualProduct1 = $this->formattedItemFactory(
            'base-02',
            'virtual product 4',
            '',
            1,
            2430,
            2430,
            true,
            true
        );
        $simpleWithoutTax = [
            '5',
            'base-01',
            'Simple product 5',
            2.0,
            22.30,
            44.60,
            0,
            0
        ];
        $formattedSimpleWithoutTax = $this->formattedItemFactory(
            'base-01',
            'Simple product 5',
            '',
            2,
            2230,
            4460,
            false,
            false
        );

        $dummyConfigurableProduct = [
            '6',
            'config-dummy-01',
            'Configurable dummy product 1',
            1.0,
            0.0,
            0.0,
            0,
            0,
            true
        ];
        $configurableProduct = [
            '7',
            'config-01',
            'Configurable product 1',
            1.0,
            24.30,
            24.30,
            0,
            1.2,
            false,
            ['simple_name' => 'Configurable product 1 - with variation']
        ];
        $formattedConfigurableProduct = $this->formattedItemFactory(
            'config-01',
            'Configurable product 1',
            'Configurable product 1 - with variation',
            1,
            2430,
            2430,
            false,
            true
        );
        return [
            '1 simple product' => [
                'products' => [['1', 'Simple product 1', ['3', '4']]],
                'items' => [$simple1],
                'expected_payload' => [
                    $formattedSimple1
                ]
            ],
            '2 simple products with 1 without image' => [
                'products' => [['1', 'Simple product 1', ['3', '4']], ['2', 'Simple product 2', ['3', '4'], null]],
                'items' => [$simple1, $simple2],
                'expected_payload' => [
                    $formattedSimple1, $formattedSimple2WithoutImage
                ]
            ],
            '1 simple product without category' => [
                'products' => [['3', 'Simple product 3', []]],
                'items' => [$simpleWithoutCategory],
                'expected_payload' => [
                    $formattedSimpleWithoutCategory
                ]
            ],
            '1 virtual product' => [
                'products' => [['4', 'virtual product 4', ['3', '4']]],
                'items' => [$virtualProduct1],
                'expected_payload' => [
                    $formattedVirtualProduct1
                ]
            ],
            '1 simple without tax' => [
                'products' => [['5', 'Simple product 5', ['3', '4']]],
                'items' => [$simpleWithoutTax],
                'expected_payload' => [
                    $formattedSimpleWithoutTax
                ]
            ],
            '1 configurable product with dummy item' => [
                'products' => [['7', 'Configurable product 1', ['3', '4']], ['6', 'Configurable dummy product 1', ['3', '4']]],
                'items' => [$configurableProduct, $dummyConfigurableProduct],
                'expected_payload' => [
                    $formattedConfigurableProduct
                ]
            ],
            '1 configurable product with dummy item and 1 simple' => [
                'products' => [['7', 'Configurable product 1', ['3', '4']], ['6', 'Configurable dummy product 1', ['3', '4']], ['1', 'Simple product 1', ['3', '4']]],
                'items' => [
                    $configurableProduct,
                    $dummyConfigurableProduct,
                    $simple1
                ],
                'expected_payload' => [
                    $formattedConfigurableProduct,
                    $formattedSimple1
                ]
            ],
        ];
    }

    private function itemFactory(
        string $pid,
        string $sku,
        string $name,
        float  $qty,
        float  $price,
        float  $rowPrice,
        bool   $isVirtual,
        float  $taxAmount,
        bool   $dummy = false,
        array  $productOptions = []
    ): Item
    {
        $item = $this->createMock(Item::class);
        $item->method('getProductId')->willReturn($pid);
        $item->method('getSku')->willReturn($sku);
        $item->method('getName')->willReturn($name);
        $item->method('getProductOptions')->willReturn($productOptions);
        $item->method('getQtyOrdered')->willReturn($qty);
        $item->method('getPriceInclTax')->willReturn($price);
        $item->method('getBaseRowTotalInclTax')->willReturn($rowPrice);
        $item->method('getIsVirtual')->willReturn($isVirtual);
        $item->method('getTaxAmount')->willReturn($taxAmount);
        $item->method('isDummy')->willReturn($dummy);
        return $item;
    }

    private function productFactory(
        string  $pid,
        string  $name,
        array   $categories = ['3', '4'],
        ?string $url = '/w/b/wb04-blue-0.jpg'
    ): Product
    {
        $product = $this->createMock(Product::class);
        $product->method('getEntityId')->willReturn($pid);
        $product->method('getName')->willReturn($name);
        $product->method('getCategoryIds')->willReturn($categories);
        $product->method('getProductUrl')->willReturn('https://adobe-commerce-a-2-4-5.local.test/fusion-backpack.html');
        $product->method('getImage')->willReturn($url);
        return $product;
    }

    private function formattedItemFactory(
        string $sku,
        string $name,
        string $variantName,
        int    $qty,
        int    $price,
        int    $rowPrice,
        bool   $isVirtual,
        bool   $taxAmount,
        array  $categories = ['Gear', 'Bags'],
        string $pictureUrl = self::MEDIA_BASE_URL . 'catalog/product/w/b/wb04-blue-0.jpg'
    ): array
    {
        return [
            'sku' => $sku,
            'vendor' => '',
            'title' => $name,
            'variant_title' => $variantName,
            'quantity' => $qty,
            'unit_price' => $price,
            'line_price' => $rowPrice,
            'is_gift' => false,
            'categories' => $categories,
            'url' => 'https://adobe-commerce-a-2-4-5.local.test/fusion-backpack.html',
            'picture_url' => $pictureUrl,
            'requires_shipping' => !$isVirtual,
            'taxes_included' => $taxAmount
        ];
    }
}
