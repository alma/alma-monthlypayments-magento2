<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Entities\Payment;
use Alma\MonthlyPayments\Helpers\PaymentHelper;
use InvalidArgumentException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use PHPUnit\Framework\TestCase;

class PaymentHelperTest extends TestCase
{
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    protected function setUp(): void
    {
        $contextMock = $this->createMock(Context::class);
        $this->paymentHelper = new PaymentHelper($contextMock);
    }

    public function testInstanceConfigHelper()
    {
        $this->assertInstanceOf(PaymentHelper::class, $this->paymentHelper);
    }

    public function testImplementAbstractHelper()
    {
        $this->assertInstanceOf(AbstractHelper::class, $this->paymentHelper);
    }

    public function testIfGettingOrderIdThrowExceptionWithBadPayment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(PaymentHelper::NO_ORDER_ID);
        $almaPaymentMock = $this->createMock(Payment::class);
        $this->paymentHelper->getOrderIdFromAlmaPayment($almaPaymentMock);
    }

    public function testIfGettingOrderIdReturnStringWhitGoodPayment(): void
    {
        $testOrderId = '1000001';
        $almaPaymentMock = $this->createMock(Payment::class);
        $almaPaymentMock->custom_data[PaymentHelper::PAYMENT_ORDER_ID_KEY] = $testOrderId;
        $result = $this->paymentHelper->getOrderIdFromAlmaPayment($almaPaymentMock);
        $this->assertSame($testOrderId, $result);
    }

    public function testGetPlanKeyForAlmaPayment(): void
    {
        $almaPaymentData = new Payment([
            'installments_count' => '3',
            'deferred_days' => '0',
            'deferred_months' => '0'
        ]);
        $this->assertEquals('general:3:0:0', $this->paymentHelper->getAlmaPaymentPlanKey($almaPaymentData));
    }
}
