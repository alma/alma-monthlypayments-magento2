<?php

namespace Alma\MonthlyPayments\Test\Unit\Observer;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Quote;
use Alma\MonthlyPayments\Observer\CheckoutSubmitAllAfter;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderModel;
use PHPUnit\Framework\TestCase;

class CheckoutSubmitAllAfterTest extends TestCase
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Observer
     *
     */
    private $observer;
    /**
     * @var Order
     */
    private $order;
    /**
     * @var Event
     */
    private $event;
    /**
     * @var Quote
     */
    private $quote;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var OrderModel
     */
    private $orderModel;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->json = $this->createMock(Json::class);
        $this->orderModel = $this->createMock(OrderModel::class);

        $this->order = $this->createMock(Order::class);
        $this->quote = $this->createMock(Quote::class);

        $this->event = $this->createMock(Event::class);
        $this->event->method('getData')->willReturnMap(
            [
                ['order', null, $this->order],
                ['quote', null, $this->quote]
            ]
        );

        $this->observer = $this->createMock(Observer::class);
        $this->observer->method('getEvent')->willReturn($this->event);
    }

    public function createCheckoutSubmitAllAfter(): CheckoutSubmitAllAfter
    {
        return new CheckoutSubmitAllAfter(...$this->getDependency());
    }

    private function getDependency(): array
    {
        return [
            $this->logger,
            $this->json,
            $this->orderModel
        ];
    }

    public function testNoInsuranceProductInOrderNotCallSave(): void
    {
        $this->orderModel->expects($this->never())->method('save');
        $this->order->method('getAllVisibleItems')->willReturn($this->getAllVisibleItemsWithoutInsurance());
        $checkoutSubmitAllAfter = $this->createCheckoutSubmitAllAfter();
        $checkoutSubmitAllAfter->execute($this->observer);
    }

    public function testInsuranceProductInOrderCallSaveAndCopyData(): void
    {
        $this->quote->method('getItemById')->willReturnMap([
            [2, $this->quoteItemFactory(2, 'simple_product_with_alma_insurance')],
            [3, $this->quoteItemFactory(3, 'alma_insurance')],
            [5, $this->quoteItemFactory(5, 'configurable_product_with_alma_insurance')],
            [6, $this->quoteItemFactory(6, 'alma_insurance')]
        ]);
        $this->orderModel->expects($this->once())->method('save');
        $this->order->method('getAllVisibleItems')->willReturn($this->getAllVisibleItemsWithInsurance());
        $checkoutSubmitAllAfter = $this->createCheckoutSubmitAllAfter();
        $checkoutSubmitAllAfter->execute($this->observer);
    }

    private function orderItemFactory(string $sku, string $type, int $quoteItemId): Order\Item
    {
        $item = $this->createMock(Order\Item::class);
        $item->method('getSku')->willReturn($sku);
        $item->method('getProductType')->willReturn($type);
        $item->method('getQuoteItemId')->willReturn($quoteItemId);
        return $item;
    }

    private function quoteItemFactory(int $quoteItemId, string $type): Order\Item
    {
        $item = $this->createMock(Order\Item::class);
        $item->expects($this->once())->method('getData')->willReturn(
            '{"id":' . $quoteItemId . ',"name":"Alma outillage thermique 3 ans (Vol + casse)","price":11,"link":,"parent_name":"Fusion Backpack","type":"' . $type . '"}'
        );
        return $item;
    }

    private function getAllVisibleItemsWithoutInsurance(): array
    {
        return [
            $this->orderItemFactory('24-MBO2', 'simple', 1),
            $this->orderItemFactory('WSM12', 'configurable', 2)
        ];
    }

    private function getAllVisibleItemsWithInsurance(): array
    {
        $simpleWithInsurance = $this->orderItemFactory('24-MBO2', 'simple_product_with_alma_insurance', 2);
        $simpleWithInsurance->expects($this->once())->method('setData')->with(
            InsuranceHelper::ALMA_INSURANCE_DB_KEY,
            '{"id":2,"name":"Alma outillage thermique 3 ans (Vol + casse)","price":11,"link":,"parent_name":"Fusion Backpack","type":"simple_product_with_alma_insurance"}'
        );
        $insuranceForSimple = $this->orderItemFactory('alma_insurance', 'alma_insurance', 3);
        $insuranceForSimple->expects($this->once())->method('setData')->with(
            InsuranceHelper::ALMA_INSURANCE_DB_KEY,
            '{"id":3,"name":"Alma outillage thermique 3 ans (Vol + casse)","price":11,"link":,"parent_name":"Fusion Backpack","type":"alma_insurance"}'
        );

        $configurableWithInsurance = $this->orderItemFactory('WSM12', 'configurable_product_with_alma_insurance', 5);
        $configurableWithInsurance->expects($this->once())->method('setData')->with(
            InsuranceHelper::ALMA_INSURANCE_DB_KEY,
            '{"id":5,"name":"Alma outillage thermique 3 ans (Vol + casse)","price":11,"link":,"parent_name":"Fusion Backpack","type":"configurable_product_with_alma_insurance"}'
        );
        $insuranceForConfigurable = $this->orderItemFactory('alma_insurance', 'alma_insurance', 6);
        $insuranceForConfigurable->expects($this->once())->method('setData')->with(
            InsuranceHelper::ALMA_INSURANCE_DB_KEY,
            '{"id":6,"name":"Alma outillage thermique 3 ans (Vol + casse)","price":11,"link":,"parent_name":"Fusion Backpack","type":"alma_insurance"}'
        );
        return [
            $this->orderItemFactory('24-MBO2', 'simple', 1),
            $simpleWithInsurance,
            $insuranceForSimple,
            $this->orderItemFactory('WSM12', 'configurable', 4),
            $configurableWithInsurance,
            $insuranceForConfigurable,
        ];
    }
}
