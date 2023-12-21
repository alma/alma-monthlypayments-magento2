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
        $protectionDays = 365;
        $description = null;
        $coverArea = null;
        $compensationArea = null;
        $exclusionArea = null;
        $uncoveredArea = null;
        $price = 10000;
        $files = [];
        $parentName = 'my parent name';
        $insuranceContract = new Contract(
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
        $expectedReturn = [
            'id' => $id,
            'name' => $name,
            'price' => 10000,
            'duration_year' => 1,
            'link' => null,
            'parent_name' => 'my parent name'
        ];
        $insuranceProduct = new InsuranceProduct($insuranceContract, $parentName);
        $this->assertEquals($expectedReturn, $insuranceProduct->toArray());
    }

}
