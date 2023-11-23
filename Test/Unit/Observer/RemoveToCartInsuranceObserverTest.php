<?php

namespace Alma\MonthlyPayments\Test\Unit\Observer;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Quote;
use Alma\MonthlyPayments\Observer\RemoveToCartInsuranceObserver;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;

class RemoveToCartInsuranceObserverTest extends TestCase
{

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var mixed
     */
    private $observer;
    /**
     * @var InsuranceHelper
     */
    private $insuranceHelper;
    /**
     * @var Item
     *
     */
    private $quoteItem;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->insuranceHelper = $this->createMock(InsuranceHelper::class);
        $this->observer = $this->createMock(Observer::class);
        $this->quoteItem = $this->createMock(Item::class);
        $this->observer->method('getData')->with('quote_item')->willReturn($this->quoteItem);
    }
    public function createRemoveToCartObserver(): RemoveToCartInsuranceObserver
    {
        return new RemoveToCartInsuranceObserver(...$this->getDependency());
    }

    protected function getDependency(): array
    {
        return [
            $this->logger,
            $this->insuranceHelper
        ];
    }

    public function testProductWithoutInsuranceDataReturnFalse():void
    {
        $this->insuranceHelper->method('getQuoteItemAlmaInsurance')->with($this->quoteItem)->willReturn(null);

		$this->insuranceHelper->expects($this->never())->method('setAlmaInsuranceToQuoteItem');
		$this->insuranceHelper->expects($this->never())->method('removeQuoteItemFromCart');
		$this->assertNull($this->createRemoveToCartObserver()->execute($this->observer));
    }

	public function testForAnInsuranceProductCallRemoveInsuranceDataOnProductLine():void
	{
		$this->quoteItem->method('getSku')->willReturn(InsuranceHelper::ALMA_INSURANCE_SKU);

		$noInsuranceItem = $this->itemFactory('sku1');
		$productWithInsurance = $this->itemFactory('remove_data');

		$quoteItems =   [
			$noInsuranceItem,
			$this->quoteItem,
			$productWithInsurance,
		];
		$mapReturn = [
			[$noInsuranceItem, null],
			[$this->quoteItem, '{"id":1,"name":"Casse","price":11,"link":"AZERTYUIOP","parent_name":"Fusion Backpack"}'],
			[$productWithInsurance, '{"id":1,"name":"Casse","price":11,"link":"AZERTYUIOP","parent_name":"Fusion Backpack"}'],
		];
		$quoteMock = $this->createMock(Quote::class);
		$this->quoteItem->method('getQuote')->willReturn($quoteMock);

		$quoteMock->method('getItems')->willReturn($quoteItems);

		$this->insuranceHelper
			->method('getQuoteItemAlmaInsurance')
			->willReturnMap($mapReturn);

		$this->insuranceHelper->expects($this->once())->method('getProductLinkedToInsurance')->with('AZERTYUIOP',$quoteItems)->willReturn($productWithInsurance);
		$this->insuranceHelper->expects($this->once())->method('setAlmaInsuranceToQuoteItem')->with($productWithInsurance);
		$this->insuranceHelper->expects($this->never())->method('getInsuranceProductToRemove');
		$this->insuranceHelper->expects($this->never())->method('removeQuoteItemFromCart');
		$this->assertNull($this->createRemoveToCartObserver()->execute($this->observer));
	}

    public function testForASimpleProductWithAssuranceRemoveLinkedAssurance():void
    {
        $noInsuranceItem = $this->itemFactory('sku1');
		$insuranceItem = $this->itemFactory(InsuranceHelper::ALMA_INSURANCE_SKU);
		$this->quoteItem->method('getSku')->willReturn('removed');

		$quoteItems =   [
			$noInsuranceItem,
			$this->quoteItem,
			$insuranceItem,
		];

		$quoteMock = $this->createMock(Quote::class);
		$this->quoteItem->method('getQuote')->willReturn($quoteMock);

        $quoteMock->method('getItems')->willReturn($quoteItems);
        $mapReturn = [
            [$noInsuranceItem, null],
			[$this->quoteItem, '{"id":1,"name":"Casse","price":11,"link":"AZERTYUIOP","parent_name":"Fusion Backpack"}'],
			[$insuranceItem, '{"id":1,"name":"Casse","price":11,"link":"AZERTYUIOP","parent_name":"Fusion Backpack"}'],
        ];

        $this->insuranceHelper
            ->method('getQuoteItemAlmaInsurance')
            ->willReturnMap($mapReturn);

        $this->insuranceHelper->expects($this->once())->method('getInsuranceProductToRemove')->with('AZERTYUIOP',$quoteItems)->willReturn($insuranceItem);
		$this->insuranceHelper->expects($this->once())->method('removeQuoteItemFromCart')->with($insuranceItem);
        $this->assertNull($this->createRemoveToCartObserver()->execute($this->observer));
    }

	private function itemFactory(string $sku):Item
	{
		$item = $this->createMock(Item::class);
		$item->method('getSku')->willReturn($sku);
		return $item;
	}
}
