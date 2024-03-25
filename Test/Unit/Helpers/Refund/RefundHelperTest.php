<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers\Refund;

use Alma\API\Client;
use Alma\API\Endpoints\Payments;
use Alma\API\Entities\Payment;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\Refund\RefundHelper;
use Codeception\PHPUnit\TestCase;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class RefundHelperTest extends TestCase
{
    /**
     * @var Context|(Context&object&\PHPUnit\Framework\MockObject\MockObject)|(Context&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;
    /**
     * @var Logger|(Logger&object&\PHPUnit\Framework\MockObject\MockObject)|(Logger&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    public function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->logger = $this->createMock(Logger::class);
    }
    public function testInstanceOffRefundHelper(): void
    {
        $almaClient = $this->createMock(AlmaClient::class);
        $refundHelper = new RefundHelper($this->context, $this->logger, $almaClient);
        $this->assertInstanceOf(RefundHelper::class, $refundHelper);
    }
    public function testExtendsAbstractHelper(): void
    {
        $almaClient = $this->createMock(AlmaClient::class);
        $refundHelper = new RefundHelper($this->context, $this->logger, $almaClient);
        $this->assertInstanceOf(AbstractHelper::class, $refundHelper);
    }

    public function testAlmaPartialRefundApiIsCallWithArgumentWhenMakeRefund(): void
    {
        $paymentId = 'test_payment_id';
        $price = 10000;
        $endpointPayment = $this->createMock(Payments::class);
        $clientMock = $this->createMock(Client::class);
        $almaClientMock = $this->createMock(AlmaClient::class);
        $almaClientMock->expects($this->once())
            ->method('getDefaultClient')
            ->willReturn($clientMock);
        $clientMock->payments = $endpointPayment;
        $endpointPayment->expects($this->once())
            ->method('partialRefund')
            ->with($paymentId, $price)
            ->willReturn($this->createMock(Payment::class));
        $refundHelper = new RefundHelper($this->context, $this->logger, $almaClientMock);
        $refundHelper->makePartialRefund($paymentId, $price);
    }

}
