<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Request\CartDataBuilder;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\ProductHelper;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollectionAlias;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class CartDataBuilderTest extends TestCase
{
    const MEDIA_BASE_URL = 'https://adobe-commerce-a-2-4-5.local.test/media/';
    /**
     * @var Logger
     */
    private $logger;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);

        $this->imageHelper = $this->createMock(Image::class);
        $this->imageHelper->method('init')->willReturn($this->imageHelper);
        $this->imageHelper->method('getUrl')->willReturn('');

        $storeInterface = $this->createMock(Store::class);
        $storeInterface->method('getBaseUrl')->willReturn(self::MEDIA_BASE_URL);

        $this->storeManagerInterface = $this->createMock(StoreManagerInterface::class);
        $this->storeManagerInterface->method('getStore')->willReturn($storeInterface);
    }
    private function getConstructorDependency(): array
    {
        return [
            $this->logger,
            $this->storeManagerInterface,
            $this->productHelper
        ];
    }
    private function createCartDataBuilderTest(): CartDataBuilder
    {
        return new CartDataBuilder(...$this->getConstructorDependency());
    }

    /**
     * @dataProvider CartPayloadDataProvider
     *
     * @return void
     */
    public function testBuild(array $dataItems, $expectedPayload):void
    {
        $products = [];
        $items = [];
        foreach ($dataItems as $data) {
            $products[] = $this->productFactory(...$data);
            $items[] = $this->itemFactory(...$data);
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
        $categoryIterator = new \ArrayIterator([$catGear,$catBag]);
        $categoriesCollectionMock->method('getIterator')->willReturn($categoryIterator);

        $this->productHelper = $this->createMock(ProductHelper::class);
        $this->productHelper->method('getProductsItems')->willReturn($productCollectionMock);
        $this->productHelper->method('getProductsCategories')->willReturn($categoriesCollectionMock);

        $cartDataBuilder = $this->createCartDataBuilderTest();
        $this->assertEquals(
            $expectedPayload,
            $cartDataBuilder->build(
                ["payment" => $this->createPaymentDataObject($items)]
            )
        );
    }

    public function cartPayloadDataProvider():array
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
        $simpleWithoutCategory = [
            '3',
            'base-02',
            'Simple product 2',
            1.0,
            24.30,
            24.30,
            0,
            1.10,
            []
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
            'Simple product 2',
            1.0,
            24.30,
            24.30,
            1,
            1.10
        ];
        $formattedVirtualProduct1 = $this->formattedItemFactory(
            'base-02',
            'Simple product 2',
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
            'Simple product 1',
            2.0,
            22.30,
            44.60,
            0,
            0
        ];
        $formattedSimpleWithoutTax = $this->formattedItemFactory(
            'base-01',
            'Simple product 1',
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
            ['3','4'],
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
            ['3','4'],
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
                'products' =>  [$simple1],
                'expected_payload' => [
                    'cart' => [
                        'items' => [
                            $formattedSimple1
                        ]
                    ]
                ]
            ],
            '2 simple products' => [
                'products' =>  [$simple1, $simple2],
                'expected_payload' => [
                    'cart' => [
                        'items' => [
                            $formattedSimple1 ,$formattedSimple2
                        ]
                    ]
                ]
            ],
            '1 simple product without category' => [
                'products' =>  [$simpleWithoutCategory],
                'expected_payload' => [
                    'cart' => [
                        'items' => [
                            $formattedSimpleWithoutCategory
                        ]
                    ]
                ]
            ],
            '1 virtual product' => [
                'products' =>  [$virtualProduct1],
                'expected_payload' => [
                    'cart' => [
                        'items' => [
                            $formattedVirtualProduct1
                        ]
                    ]
                ]
            ],
            '1 simple without tax' => [
                'products' =>  [$simpleWithoutTax],
                'expected_payload' => [
                    'cart' => [
                        'items' => [
                            $formattedSimpleWithoutTax
                        ]
                    ]
                ]
            ],
            '1 configurable product with dummy item' => [
                'products' =>  [$configurableProduct, $dummyConfigurableProduct],
                'expected_payload' => [
                    'cart' => [
                        'items' => [
                            $formattedConfigurableProduct
                        ]
                    ]
                ]
            ],
            '1 configurable product with dummy item and 1 simple' => [
                'products' =>  [
                        $configurableProduct,
                        $dummyConfigurableProduct,
                        $simple1
                ],
                'expected_payload' => [
                    'cart' => [
                        'items' => [
                            $formattedConfigurableProduct,
                            $formattedSimple1
                        ]
                    ]
                ]
            ],
        ];
    }

    private function createPaymentDataObject(array $items):PaymentDataObjectInterface
    {
        $orderAdapter = $this->createMock(OrderAdapterInterface::class);
        $orderAdapter->method('getItems')->willReturn($items);

        $paymentDataObject = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDataObject->method('getOrder')->willReturn($orderAdapter);
        return $paymentDataObject;
    }

    private function itemFactory(
        string $pid,
        string $sku,
        string $name,
        float $qty,
        float $price,
        float $rowPrice,
        bool $isVirtual,
        float $taxAmount,
        array $categories = ['3','4'],
        bool $dummy = false,
        array $productOptions= []
    ):Item {
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
        string $pid,
        string $sku,
        string $name,
        float $qty,
        float $price,
        float $rowPrice,
        bool $isVirtual,
        float $taxAmount,
        array $categories = ['3','4'],
        bool $dummy = false,
        array $productOptions= []
    ):Product {
        $product = $this->createMock(Product::class);
        $product->method('getEntityId')->willReturn($pid);
        $product->method('getName')->willReturn($name);
        $product->method('getCategoryIds')->willReturn($categories);
        $product->method('getProductUrl')->willReturn('https://adobe-commerce-a-2-4-5.local.test/fusion-backpack.html');
        $product->method('getImage')->willReturn('/w/b/wb04-blue-0.jpg');
        return $product;
    }

    private function formattedItemFactory(
        string $sku,
        string $name,
        string $variantName,
        int $qty,
        int $price,
        int $rowPrice,
        bool $isVirtual,
        bool $taxAmount,
        array $categories =  ['Gear','Bags']
    ):array {
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
            'picture_url' => self::MEDIA_BASE_URL . 'catalog/product' . '/w/b/wb04-blue-0.jpg',
            'requires_shipping' => !$isVirtual,
            'taxes_included' => $taxAmount
        ];
    }
}
