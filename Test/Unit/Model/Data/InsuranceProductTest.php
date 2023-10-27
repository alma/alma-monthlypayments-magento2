<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Data;


use Alma\MonthlyPayments\Model\Data\InsuranceProduct;
use PHPUnit\Framework\TestCase;

class InsuranceProductTest extends TestCase
{
    public function testReturnDataInArray():void
    {
        $id =1;
        $name ='insurance test';
        $price = 100;
        $expectedReturn = [
            'id' => $id,
            'name' => $name,
            'price' => 100.0,
        ];
        $insuranceProduct = New InsuranceProduct($id, $name, $price);
        $this->assertEquals($expectedReturn, $insuranceProduct->toArray());
    }

}
