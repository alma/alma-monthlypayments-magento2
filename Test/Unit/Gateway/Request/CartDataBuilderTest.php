<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Request\CartDataBuilder;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Item;
use PHPUnit\Framework\TestCase;

class CartDataBuilderTest extends TestCase
{

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

        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->categoryRepository->method('get')->withConsecutive(['3'], ['4'])->willReturnOnConsecutiveCalls($catGear, $catBag);
    }
    private function getConstructorDependency(): array
    {
        return [
            $this->logger,
            $this->categoryRepository
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
            false,
            2.10
        );
        $formattedSimple1 = $this->formattedItemFactory(
            'base-01',
            'Simple product 1',
            2,
            2230,
            4460,
            false,
            2.10
        );

        return [
            'Only 1 simple product' => [
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
            ]
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
        float $taxAmount
    ):Item {
        $product = $this->createMock(Product::class);
        $product->method('getCategoryIds')->willReturn(['3','4']);
        $product->method('getProductUrl')->willReturn('http://adobe-commerce-a-2-4-5.local.test/fusion-backpack.html');
        $product->method('getImage')->willReturn('/w/b/wb04-blue-0.jpg');

        $item = $this->createMock(Item::class);
        $item->method('getSku')->willReturn($sku);
        $item->method('getName')->willReturn($name);
        $item->method('getQtyOrdered')->willReturn($qty);
        $item->method('getPriceInclTax')->willReturn($price);
        $item->method('getBaseRowTotalInclTax')->willReturn($rowPrice);
        $item->method('getProduct')->willReturn($product);
        $item->method('getIsVirtual')->willReturn($isVirtual);
        $item->method('getTaxAmount')->willReturn($taxAmount);
        return $item;
    }

    private function formattedItemFactory(
        string $sku,
        string $name,
        int $qty,
        int $price,
        int $rowPrice,
        bool $isVirtual,
        float $taxAmount
    ):array {
        return [
            'sku' => $sku,
            'vendor' => '',
            'title' => $name,
            'variant_title' => '',
            'quantity' => $qty,
            'unit_price' => $price,
            'line_price' => $rowPrice,
            'is_gift' => false,
            'categories' => ['Gear','Bags'],
            'url' => 'http://adobe-commerce-a-2-4-5.local.test/fusion-backpack.html',
            'picture_url' => '/w/b/wb04-blue-0.jpg',
            'requires_shipping' => $isVirtual,
            'taxes_included' => $taxAmount
        ];
    }
}
