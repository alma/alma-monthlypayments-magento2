<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Data;

use Alma\MonthlyPayments\Model\Data\InsuranceConfig;
use Codeception\PHPUnit\TestCase;

class InsuranceConfigTest extends TestCase
{

    /**
     * @dataProvider jsonDataProvider
     * @param $isAllowed
     * @param $jsonData
     * @param $result
     * @return void
     */
    public function testGetters($isAllowed, $jsonData, $result)
    {
        $insuranceConfig = new InsuranceConfig($isAllowed, json_encode($jsonData));
        $this->assertEquals($result['isInsuranceActivated'], $insuranceConfig->isActivated());
        $this->assertEquals($result['isAllowed'], $insuranceConfig->isAllowed());
        $this->assertEquals($result['isInsuranceOnProductPageActivated'], $insuranceConfig->isPageActivated());
        $this->assertEquals($result['isAddToCartPopupActivated'], $insuranceConfig->isPopupActivated());
        $this->assertEquals($result['isInCartWidgetActivated'], $insuranceConfig->isCartActivated());
    }

    protected function jsonDataProvider(): array
    {
        return [
            'json with mixed data' => [
                'isAllowed' => true,
                'jsonData' => [
                    'isInsuranceActivated' => false,
                    'isInsuranceOnProductPageActivated' => true,
                    'isAddToCartPopupActivated' => false,
                    'isInCartWidgetActivated' => true
                ],
                'result' => [
                    'isAllowed' => true,
                    'isInsuranceActivated' => false,
                    'isInsuranceOnProductPageActivated' => true,
                    'isAddToCartPopupActivated' => false,
                    'isInCartWidgetActivated' => true
                ]
            ],
            'json with other mixed data' => [
                'isAllowed' => true,
                'jsonData' => [
                    'isInsuranceActivated' => true,
                    'isInsuranceOnProductPageActivated' => false,
                    'isAddToCartPopupActivated' => true,
                    'isInCartWidgetActivated' => false
                ],
                'result' => [
                    'isAllowed' => true,
                    'isInsuranceActivated' => true,
                    'isInsuranceOnProductPageActivated' => false,
                    'isAddToCartPopupActivated' => true,
                    'isInCartWidgetActivated' => false
                ]
            ],
            'test default values' => [
                'isAllowed' => false,
                'jsonData' => [],
                'result' => [
                    'isAllowed' => false,
                    'isInsuranceActivated' => false,
                    'isInsuranceOnProductPageActivated' => false,
                    'isAddToCartPopupActivated' => false,
                    'isInCartWidgetActivated' => false
                ]
            ],
            'test not boolean values' => [
                'isAllowed' => true,
                'jsonData' => [
                    'isInsuranceActivated' => 'Toto',
                    'isInsuranceOnProductPageActivated' => 'true',
                    'isAddToCartPopupActivated' => 'true',
                    'isInCartWidgetActivated' => 'true'
                ],
                'result' => [
                    'isAllowed' => true,
                    'isInsuranceActivated' => false,
                    'isInsuranceOnProductPageActivated' => false,
                    'isAddToCartPopupActivated' => false,
                    'isInCartWidgetActivated' => false
                ]
            ],
            'test null values' => [
                'isAllowed' => true,
                'jsonData' => [
                    'isInsuranceActivated' => null,
                    'isInsuranceOnProductPageActivated' => null,
                    'isAddToCartPopupActivated' => null,
                    'isInCartWidgetActivated' => null
                ],
                'result' => [
                    'isAllowed' => true,
                    'isInsuranceActivated' => false,
                    'isInsuranceOnProductPageActivated' => false,
                    'isAddToCartPopupActivated' => false,
                    'isInCartWidgetActivated' => false
                ]
            ]
        ];
    }
}
