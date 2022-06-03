<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Entities\Payment;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\OrderHelper;
use Alma\MonthlyPayments\Helpers\PaymentValidation;
use Magento\Checkout\Model\Session;
use Magento\Directory\Model\Currency;
use Magento\Framework\Phrase;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\Processor;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use PHPUnit\Framework\TestCase;

class PaymentValidationTest extends TestCase
{
    const TXT_PRICE = '1 012,20 €';
    const CREATED_DATE = '1654114331';
    const BASE_DEFFERED_DAYS = '30';


    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->checkoutSession = $this->createMock(Session::class);
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->paymentProcessor = $this->createMock(Processor::class);
        $this->quoteRepository = $this->createMock(QuoteRepository::class);
        $this->builderInterface = $this->createMock(BuilderInterface::class);
        $this->orderHelper = $this->createMock(OrderHelper::class);
    }

    /**
     * @dataProvider paymentDataProvider
     */
    public function testCreateTransaction($data, $result): void
    {
        $paymentValidation = $this->createNewPaymentValidation();
        $price = 100;
        $orderMock = $this->createMock(Order::class);
        $orderMock->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn($price);
        $currencyMock = $this->createMock(Currency::class);
        $currencyMock->expects($this->once())
            ->method('formatTxt')
            ->with($price)
            ->willReturn(self::TXT_PRICE);
        $orderMock->expects($this->once())
            ->method('getBaseCurrency')
            ->willReturn($currencyMock);
        $almaPaymentMock = $this->createMock(Payment::class);
        $almaPaymentMock->created = $data['created'];
        $almaPaymentMock->deferred_days = $data['deferred_days'];
        $almaPaymentMock->installments_count = '0';
        $almaPaymentMock->deferred_months = '0';
        $almaPaymentMock->deferred_trigger = $data['deferred_trigger'];
        $almaPaymentMock->deferred_trigger_description = $data['deferred_trigger_description'];
        $paymentData = $paymentValidation->createPaymentData($orderMock, $almaPaymentMock);
        $this->assertEquals($result, $paymentData);
    }

    public function testAddTransactionToPaymentStructure(): void
    {
        $paymentMock = $this->createMock(OrderPayment::class);
        $paymentMock->expects($this->once())
            ->method('setParentTransactionId')
            ->with(null)
            ->willReturnSelf();
        $orderMock = $this->createMock(Order::class);
        $almaPaymentMock = $this->createMock(Payment::class);

        $this->builderInterface->expects($this->once())
            ->method('setPayment')
            ->willReturnSelf();
        $this->builderInterface->expects($this->once())
            ->method('setOrder')
            ->willReturnSelf();
        $this->builderInterface->expects($this->once())
            ->method('setTransactionId')
            ->willReturnSelf();
        $this->builderInterface->expects($this->once())
            ->method('setAdditionalInformation')
            ->willReturnSelf();
        $this->builderInterface->expects($this->once())
            ->method('setFailSafe')
            ->with(true)
            ->willReturnSelf();
        $this->builderInterface->expects($this->once())
            ->method('build')
            ->with(TransactionInterface::TYPE_PAYMENT)
            ->willReturn($this->createMock(TransactionInterface::class));
        $paymentValidation = $this->getMockBuilder(PaymentValidation::class)
            ->onlyMethods(['createPaymentData','addTransactionComment'])
            ->setConstructorArgs($this->getConstructorDependency())
            ->getMock();
        $paymentValidation->expects($this->once())
            ->method('createPaymentData');
        $paymentValidation->expects($this->once())
            ->method('addTransactionComment')
            ->willReturn($paymentMock);
        $paymentValidation->addTransactionToPayment($paymentMock, $orderMock, $almaPaymentMock);
    }

    public function testAddTransactionCommentStructure(): void
    {
        $paymentValidation = $this->createNewPaymentValidation();
        $transactionInterfaceMock = $this->createMock(TransactionInterface::class);
        $orderMock = $this->createMock(Order::class);
        $orderMock->expects($this->once())
            ->method('getGrandTotal');
        $currencyMock = $this->createMock(Currency::class);
        $currencyMock->expects($this->once())
            ->method('formatTxt')
            ->willReturn(self::TXT_PRICE);
        $orderMock->expects($this->once())
            ->method('getBaseCurrency')
            ->willReturn($currencyMock);
        $orderPaymentInterfaceMock = $this->createMock(OrderPayment::class);
        $orderPaymentInterfaceMock->expects($this->once())
            ->method('addTransactionCommentsToOrder');
        $addTransactionComment = $paymentValidation->addTransactionComment($orderMock, $orderPaymentInterfaceMock, $transactionInterfaceMock);
        $this->assertEquals($orderPaymentInterfaceMock, $addTransactionComment);
    }

    public function testCancelOrderProcess(): void
    {
        $orderId = '14';
        $orderMock = $this->createMock(Order::class);
        $orderMock->expects($this->once())
            ->method('getId')
            ->willReturn($orderId);

        $this->orderHelper->expects($this->once())
            ->method('saveOrderInRepository')
            ->with($orderMock);
        $this->orderHelper->expects($this->once())
            ->method('cancelOrderById')
            ->with($orderId);

        $paymentValidation = $this->getMockBuilder(PaymentValidation::class)
            ->onlyMethods(['addCommentToOrder'])
            ->setConstructorArgs($this->getConstructorDependency())
            ->getMock();
        $paymentValidation->expects($this->once())
            ->method('addCommentToOrder')
            ->with($orderMock, 'internal error render', Order::STATUS_FRAUD)
            ->willReturn($orderMock);

        $phraseMock = $this->createMock(Phrase::class);
        $phraseMock->expects($this->exactly(2))
            ->method('render')
            ->willReturn('internal error render');
        $paymentValidation->cancelOrderWithComment($phraseMock, true, $orderMock);
    }

    private function createNewPaymentValidation(): PaymentValidation
    {
        return new PaymentValidation(
            $this->logger,
            $this->checkoutSession,
            $this->almaClient,
            $this->paymentProcessor,
            $this->quoteRepository,
            $this->builderInterface,
            $this->orderHelper
        );
    }
    private function getConstructorDependency(): array
    {
        return [
            $this->logger,
            $this->checkoutSession,
            $this->almaClient,
            $this->paymentProcessor,
            $this->quoteRepository,
            $this->builderInterface,
            $this->orderHelper
        ];
    }

    public function paymentDataProvider(): array
    {
        return [
            'Check with deferred Trigger True' =>
                [
                    [
                        'created' => self::CREATED_DATE,
                        'deferred_days' => self::BASE_DEFFERED_DAYS,
                        'deferred_trigger' => true,
                        'deferred_trigger_description' => 'At shipping'
                    ],
                    [
                        'total' => self::TXT_PRICE,
                        'created' => self::CREATED_DATE,
                        'deferred_days' => self::BASE_DEFFERED_DAYS,
                        'installments_count' => '0',
                        'deferred_months' => '0',
                        'deferred_trigger' => 'yes',
                        'deferred_trigger_description' => 'At shipping'
                    ]
                ],
            'Check with deferred Trigger False' => [
                [
                    'created' => self::CREATED_DATE,
                    'deferred_days' => self::BASE_DEFFERED_DAYS,
                    'deferred_trigger' => false,
                    'deferred_trigger_description' => ''
                ],
                [
                    'total' => self::TXT_PRICE,
                    'created' => self::CREATED_DATE,
                    'deferred_days' => self::BASE_DEFFERED_DAYS,
                    'installments_count' => '0',
                    'deferred_months' => '0',
                    'deferred_trigger' => 'no',
                    'deferred_trigger_description' => ''
                ]
            ]
        ];
    }
}
