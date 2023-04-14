<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Request\CartDataBuilder;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class CartDataBuilderTest extends TestCase
{
    const MEDIA_BASE_URL = 'http://adobe-commerce-a-2-4-5.local.test/media/';
    /**
     * @var Logger
     */
    private $logger;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);

        $catGear = $this->createMock(CategoryInterface::class);
        $catGear->method('getName')->willReturn('Gear');
        $catBag = $this->createMock(CategoryInterface::class);
        $catBag->method('getName')->willReturn('Bags');
        $this->imageHelper = $this->createMock(Image::class);
        $this->imageHelper->method('init')->willReturn($this->imageHelper);
        $this->imageHelper->method('getUrl')->willReturn('');

        $categoryMap = ['3'=>$catGear,'4'=>$catBag];
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->categoryRepository->expects($this->any())->method('get')->with($this->isType('string'))->will($this->returnCallback(function ($argument) use ($categoryMap) {
            if (!isset($categoryMap[$argument])) {
                return '';
            }
            return $categoryMap[$argument];
        }));

        $storeInterface = $this->createMock(Store::class);
        $storeInterface->method('getBaseUrl')->willReturn(self::MEDIA_BASE_URL);

        $this->storeManagerInterface = $this->createMock(StoreManagerInterface::class);
        $this->storeManagerInterface->method('getStore')->willReturn($storeInterface);
    }
    private function getConstructorDependency(): array
    {
        return [
            $this->logger,
            $this->categoryRepository,
            $this->storeManagerInterface
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
    public function testBuild($dataObject, $expectedPayload):void
    {
        $cartDataBuilder = $this->createCartDataBuilderTest();
        try {
            $this->assertEquals($expectedPayload, $cartDataBuilder->build($dataObject));
        } catch (NoSuchEntityException | LocalizedException $e) {
        }
    }

    public function cartPayloadDataProvider():array
    {
        $simple1 = $this->itemFactory(
            'base-01',
            'Simple product 1',
            2.0,
            22.30,
            44.60,
            0,
            2.10
        );
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
        $simple2 = $this->itemFactory(
            'base-02',
            'Simple product 2',
            1.0,
            24.30,
            24.30,
            0,
            1.10
        );
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
        $simpleWithoutCategory = $this->itemFactory(
            'base-02',
            'Simple product 2',
            1.0,
            24.30,
            24.30,
            0,
            1.10,
            []
        );
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
        $virtualProduct1 = $this->itemFactory(
            'base-02',
            'Simple product 2',
            1.0,
            24.30,
            24.30,
            1,
            1.10
        );
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
        $simpleWithoutTax = $this->itemFactory(
            'base-01',
            'Simple product 1',
            2.0,
            22.30,
            44.60,
            0,
            0
        );
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

        $dummyConfigurableProduct = $this->itemFactory(
            'config-dummy-01',
            'Configurable dummy product 1',
            1.0,
            0.0,
            0.0,
            0,
            0,
            ['3','4'],
            true
        );
        $configurableProduct = $this->itemFactory(
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
        );
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
                'data_object' => [
                    'payment' => $this->createPaymentDataObject([$simple1])
                ],
                'expected_payload' => [
                    'cart' => [
                        'items' => [
                            $formattedSimple1
                        ]
                    ]
                ]
            ],
            '2 simple products' => [
                'data_object' => [
                    'payment' => $this->createPaymentDataObject([$simple1, $simple2])
                ],
                'expected_payload' => [
                    'cart' => [
                        'items' => [
                            $formattedSimple1 ,$formattedSimple2
                        ]
                    ]
                ]
            ],
            '1 simple product without category' => [
                'data_object' => [
                    'payment' => $this->createPaymentDataObject([$simpleWithoutCategory])
                ],
                'expected_payload' => [
                    'cart' => [
                        'items' => [
                            $formattedSimpleWithoutCategory
                        ]
                    ]
                ]
            ],
            '1 virtual product' => [
                'data_object' => [
                    'payment' => $this->createPaymentDataObject([$virtualProduct1])
                ],
                'expected_payload' => [
                    'cart' => [
                        'items' => [
                            $formattedVirtualProduct1
                        ]
                    ]
                ]
            ],
            '1 simple without tax' => [
                'data_object' => [
                    'payment' => $this->createPaymentDataObject([$simpleWithoutTax])
                ],
                'expected_payload' => [
                    'cart' => [
                        'items' => [
                            $formattedSimpleWithoutTax
                        ]
                    ]
                ]
            ],
            '1 configurable product with dummy item' => [
                'data_object' => [
                    'payment' => $this->createPaymentDataObject([$configurableProduct, $dummyConfigurableProduct])
                ],
                'expected_payload' => [
                    'cart' => [
                        'items' => [
                            $formattedConfigurableProduct
                        ]
                    ]
                ]
            ],
            '1 configurable product with dummy item and 1 simple' => [
                'data_object' => [
                    'payment' => $this->createPaymentDataObject([
                        $configurableProduct,
                        $dummyConfigurableProduct,
                        $simple1
                    ])
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
        $product = $this->createMock(Product::class);
        $product->method('getCategoryIds')->willReturn($categories);
        $product->method('getProductUrl')->willReturn('http://adobe-commerce-a-2-4-5.local.test/fusion-backpack.html');
        $product->method('getImage')->willReturn('/w/b/wb04-blue-0.jpg');

        $item = $this->createMock(Item::class);
        $item->method('getSku')->willReturn($sku);
        $item->method('getName')->willReturn($name);
        $item->method('getProductOptions')->willReturn($productOptions);
        $item->method('getQtyOrdered')->willReturn($qty);
        $item->method('getPriceInclTax')->willReturn($price);
        $item->method('getBaseRowTotalInclTax')->willReturn($rowPrice);
        $item->method('getProduct')->willReturn($product);
        $item->method('getIsVirtual')->willReturn($isVirtual);
        $item->method('getTaxAmount')->willReturn($taxAmount);
        $item->method('isDummy')->willReturn($dummy);
        return $item;
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
            'url' => 'http://adobe-commerce-a-2-4-5.local.test/fusion-backpack.html',
            'picture_url' => self::MEDIA_BASE_URL . 'catalog/product' . '/w/b/wb04-blue-0.jpg',
            'requires_shipping' => !$isVirtual,
            'taxes_included' => $taxAmount
        ];
    }
}
