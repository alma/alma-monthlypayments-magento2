<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Data;


use Alma\API\Entities\Insurance\Contract;
use Alma\MonthlyPayments\Model\Data\InsuranceProduct;
use Magento\Catalog\Api\Data\ProductInterface;
use PHPUnit\Framework\TestCase;

class InsuranceProductTest extends TestCase
{
    public function testReturnDataInArray(): void
    {
        $id = 'insurance_contract_6hjsKIAhBMGCW69BAQepUN';
        $name = 'insurance test';
        $insuranceContract = $this->contractFactory($id, $name);
        $quoteItemMock = $this->createMock(ProductInterface::class);
        $quoteItemMock->method('getPrice')->willReturn(59.00);
        $quoteItemMock->method('getName')->willReturn('my parent name');
        $expectedReturn = [
            'id' => $id,
            'name' => $name,
            'price' => 10023,
            'duration_year' => 1,
            'link' => null,
            'parent_name' => 'my parent name',
            'parent_price' => 5900,
            'files' => []
        ];
        $insuranceProduct = new InsuranceProduct($insuranceContract, $quoteItemMock);
        $this->assertEquals($expectedReturn, $insuranceProduct->toArray());
    }

    public function testGetFloatPrice(): void
    {
        $id = 'insurance_contract_6hjsKIAhBMGCW69BAQepUN';
        $name = 'insurance test';
        $insuranceContract = $this->contractFactory($id, $name);
        $quoteItemMock = $this->createMock(ProductInterface::class);
        $quoteItemMock->method('getPrice')->willReturn(5900);
        $quoteItemMock->method('getName')->willReturn('my parent name');

        $insuranceProduct = new InsuranceProduct($insuranceContract, $quoteItemMock);

        $this->assertEquals(100.23, $insuranceProduct->getFloatPrice());
    }

    private function contractFactory(string $id, string $name): Contract
    {
        $protectionDays = 365;
        $description = null;
        $coverArea = null;
        $compensationArea = null;
        $exclusionArea = null;
        $uncoveredArea = null;
        $price = 10023;
        $files = [];
        return new Contract(
            $id,
            $name,
            $protectionDays,
            $description,
            $coverArea,
            $compensationArea,
            $exclusionArea,
            $uncoveredArea,
            $price,
            $files
        );
    }

}
