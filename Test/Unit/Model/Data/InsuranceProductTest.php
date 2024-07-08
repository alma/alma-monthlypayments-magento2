<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Data;


use Alma\API\Entities\Insurance\Contract;
use Alma\MonthlyPayments\Model\Data\InsuranceProduct;
use PHPUnit\Framework\TestCase;

class InsuranceProductTest extends TestCase
{
    public function testReturnDataInArray(): void
    {
        $id = 'insurance_contract_6hjsKIAhBMGCW69BAQepUN';
        $name = 'insurance test';
        $insuranceContract = $this->contractFactory($id, $name);
        $expectedReturn = [
            'id' => $id,
            'name' => $name,
            'price' => 10023,
            'duration_year' => 1,
            'link' => null,
            'parent_sku' => 'my_sku',
            'parent_name' => 'my parent name',
            'parent_price' => 1410,
            'files' => []
        ];
        $insuranceProduct = new InsuranceProduct($insuranceContract,'my_sku', 'my parent name', 14.10);
        $this->assertEquals($expectedReturn, $insuranceProduct->toArray());
    }

    public function testGetPrices(): void
    {
        $id = 'insurance_contract_6hjsKIAhBMGCW69BAQepUN';
        $name = 'insurance test';
        $insuranceContract = $this->contractFactory($id, $name);

        $insuranceProduct = new InsuranceProduct($insuranceContract,'my_sku', 'my parent name', 14.20);

        $this->assertEquals(100.23, $insuranceProduct->getFloatPrice());
        $this->assertEquals(1420, $insuranceProduct->getParentPrice());
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
