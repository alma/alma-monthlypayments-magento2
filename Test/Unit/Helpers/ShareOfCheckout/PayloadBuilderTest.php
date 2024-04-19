<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers\ShareOfCheckout;

use Alma\MonthlyPayments\Helpers\ShareOfCheckout\DateHelper;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\OrderHelper;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\PayloadBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use PHPUnit\Framework\TestCase;

class PayloadBuilderTest extends TestCase
{
    private $payloadBuilder;
    /**
     * @var DateHelper
     */
    private $dateHelper;
    /**
     * @var OrderHelper
     */
    private $orderHelper;

    public function setUp(): void
    {
        $context = $this->createMock(Context::class);
        $this->dateHelper = $this->createMock(DateHelper::class);
        $this->orderHelper = $this->createMock(OrderHelper::class);
        $this->payloadBuilder = new PayloadBuilder($context, $this->dateHelper, $this->orderHelper);
    }

    public function tearDown(): void
    {
        $this->payloadBuilder = null;
    }

    public function testInstancePayloadBuilder(): void
    {
        $this->assertInstanceOf(PayloadBuilder::class, $this->payloadBuilder);
    }

    public function testImplementAbstractHelperInterface(): void
    {
        $this->assertInstanceOf(AbstractHelper::class, $this->payloadBuilder);
    }

    public function testPayloadBuilderFormat(): void
    {
        $expectedResult = [
            'start_time' => '',
            'end_time' => '',
            'orders' => [],
            'payment_methods' => []
        ];
        $this->assertEquals($expectedResult, $this->payloadBuilder->getPayload());
    }

}
