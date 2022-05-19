<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Entities\Payment;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\PaymentHelper;
use InvalidArgumentException;
use Magento\Framework\App\Helper\AbstractHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Helper\Context;

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
        $this->assertInstanceOf(PaymentHelper::class,$this->paymentHelper);
    }

    public function testImplementAbstractHelper()
    {
        $this->assertInstanceOf(AbstractHelper::class,$this->paymentHelper);
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
        $almaPaymentMock->custom_data[PaymentHelper::PAYMENT_ORDER_ID_KEY]=$testOrderId;
        $result = $this->paymentHelper->getOrderIdFromAlmaPayment($almaPaymentMock);
        $this->assertSame($testOrderId,$result);
    }

}
