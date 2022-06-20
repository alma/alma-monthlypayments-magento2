<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Helpers\Functions;
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{

    /**
     * @dataProvider priceDataProvider
     */
    public function testConvertPriceToCent($price, $result): void
    {
        $this->assertEquals($result, Functions::priceToCents($price));
    }

    public function priceDataProvider(): array
    {
        return [
            'Test with int Price' => [
                'price' => 45,
                'result' => 4500
            ],
            'Test with string price' => [
                'price' => '42',
                'result' => 4200
            ],
            'Test with float price 3 deci' => [
                'price' => 12.133,
                'result' => 1213
            ],
            'Test with negative price' => [
                'price' => -55,
                'result' => -5500
            ],
            'Test with float price' => [
                'price' => 12.13,
                'result' => 1213
            ],
            'Test with string float price' => [
                'price' => '12.12',
                'result' => 1212
            ],
            'Test with string negative float price' => [
                'price' => '-13.12',
                'result' => -1312
            ],
        ];
    }

}
