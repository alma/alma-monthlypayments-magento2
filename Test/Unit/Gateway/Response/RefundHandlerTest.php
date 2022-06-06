<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\API\Entities\Payment as AlmaPayment;
use Alma\API\Entities\Refund;
use Alma\MonthlyPayments\Gateway\Response\RefundHandler;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use PHPUnit\Framework\TestCase;

class RefundHandlerTest extends TestCase
{
    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->transactionBuilder = $this->createMock(BuilderInterface::class);
    }

    public function testRefundHandlerIsInstanceOffHandlerInterface(): void
    {
        $this->assertInstanceOf(HandlerInterface::class, $this->createNewRefundHandler());
    }

    private function createNewRefundHandler(): RefundHandler
    {
        return new RefundHandler(...$this->getConstructorDependency());
    }

    public function testTransactionIsSetWithLastRefundParams(): void
    {

        $paymentMock =  $this->createMock(Payment::class);
        $paymentMock->expects($this->exactly(2))
            ->method('formatPrice')
            ->with('48.0')
            ->willReturn('€48.00');
        $paymentMock->expects($this->once())
            ->method('setTransactionId')
            ->with('refund_3333333333');
        $paymentMock->expects($this->once())
            ->method('addTransaction')
            ->with(TransactionInterface::TYPE_REFUND);
        $paymentDataMock =  $this->createMock(PaymentDataObject::class);
        $paymentDataMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $handlingSubjectMock['payment'] = $paymentDataMock;
        $handlingSubjectMock['amount'] = '48';
        $almaPaymentMock =  $this->createMock(AlmaPayment::class);
        $almaPaymentMock->refunds = $this->getAlmaRefunds();
        $responseMock['almaRefund'] = $almaPaymentMock;
        $refundHandler = $this->createNewRefundHandler();
        $refundHandler->handle($handlingSubjectMock, $responseMock);
    }

    private function getAlmaRefunds(): array
    {
        return [
            new Refund(['id' => 'refund_1111111111','created' => '1654472700','amount' => '3800']),
            new Refund(['id' => 'refund_2222222222','created' => '1654472720','amount' => '4800']),
            new Refund(['id' => 'refund_3333333333','created' => '1654472730','amount' => '4800']),
        ];
    }

    private function getConstructorDependency(): array
    {
        return [
            $this->logger,
            $this->transactionBuilder
        ];
    }
}
