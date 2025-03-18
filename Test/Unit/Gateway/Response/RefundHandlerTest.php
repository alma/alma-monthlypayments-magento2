<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\API\Entities\Payment as AlmaPayment;
use Alma\API\Entities\Refund;
use Alma\MonthlyPayments\Gateway\Response\RefundHandler;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use PHPUnit\Framework\TestCase;

class RefundHandlerTest extends TestCase
{

    const FIXED_TIMESTAMP = '1654472730';

    public function testRefundHandlerIsInstanceOffHandlerInterface(): void
    {
        $this->assertInstanceOf(HandlerInterface::class, $this->createNewRefundHandler());
    }

    private function createNewRefundHandler(): RefundHandler
    {
        return new RefundHandler();
    }

    /**
     * @dataProvider refundDataProvider
     */
    public function testTransactionIsSetWithLastRefundParams($provider): void
    {
        $paymentMock = $this->createMock(Payment::class);
        $paymentMock->expects($this->any())
            ->method('formatPrice')
            ->willReturnOnConsecutiveCalls(...$provider['formatPriceReturn']);
        $paymentMock->expects($this->once())
            ->method('setTransactionId')
            ->with($provider['lastRefundId']);
        $paymentMock->expects($this->once())
            ->method('setTransactionAdditionalInfo')
            ->with(Transaction::RAW_DETAILS, $provider['lastRefundData']);
        $paymentMock->expects($this->once())
            ->method('addTransaction')
            ->with(TransactionInterface::TYPE_REFUND);
        $paymentDataMock = $this->createMock(PaymentDataObject::class);
        $paymentDataMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $handlingSubjectMock['payment'] = $paymentDataMock;
        $handlingSubjectMock['amount'] = '48';

        $almaPaymentMock = $this->createMock(AlmaPayment::class);
        $almaPaymentMock->refunds = $provider['refunds'];
        $responseMock['almaRefund'] = $almaPaymentMock;
        $responseMock['isFullRefund'] = $provider['isFullRefund'];
        $responseMock['customerFee'] = $provider['customerFee'];
        $refundHandler = $this->createNewRefundHandler();
        $refundHandler->handle($handlingSubjectMock, $responseMock);
    }

    public function refundDataProvider(): array
    {
        return [
            'Test not full refund' => [
                [
                    'isFullRefund' => false,
                    'customerFee' => 0,
                    'refunds' => $this->getAlmaRefunds(),
                    'formatPriceReturn' => ['€22', '€48', '€48'],
                    'lastRefundId' => 'refund_3333333333',
                    'lastRefundData' => [
                        'created' => self::FIXED_TIMESTAMP,
                        'amount' => '€48'
                    ]
                ]
            ],
            'Test with full refund' => [
                [
                    'isFullRefund' => true,
                    'customerFee' => 1600,
                    'refunds' => $this->getAlmaRefunds(),
                    'formatPriceReturn' => ['€22', '€48', '€48', '€16', '€48'],
                    'lastRefundId' => 'refund_3333333333',
                    'lastRefundData' => [
                        'created' => self::FIXED_TIMESTAMP,
                        'amount' => '€48',
                        'customer_fee' => '€16',
                        'magento_refund' => '€48'
                    ]
                ]
            ]
        ];
    }

    private function getAlmaRefunds(): array
    {
        return [
            new Refund(['id' => 'refund_1111111111', 'created' => self::FIXED_TIMESTAMP, 'amount' => '2200']),
            new Refund(['id' => 'refund_2222222222', 'created' => self::FIXED_TIMESTAMP, 'amount' => '4800']),
            new Refund(['id' => 'refund_3333333333', 'created' => self::FIXED_TIMESTAMP, 'amount' => '4800']),
        ];
    }
}
