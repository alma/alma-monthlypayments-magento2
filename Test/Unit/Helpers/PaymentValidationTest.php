<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Client;
use Alma\API\Endpoints\Payments;
use Alma\API\Entities\Payment;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\OrderHelper;
use Alma\MonthlyPayments\Helpers\PaymentValidation;
use Alma\MonthlyPayments\Model\Exceptions\AlmaPaymentValidationException;
use Magento\Checkout\Model\Session;
use Magento\Directory\Model\Currency;
use Magento\Framework\Phrase;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\Processor;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PaymentValidationTest extends TestCase
{
    const TXT_PRICE = '1 012,20 €';
    const FIXED_TIMESTAMP = '1654114331';
    const DEFFERED_DAYS_30 = '30';
    const PAYMENT_ID = 'payment_11upE7m4owxuD78NBymjsYGD6xzxL3KKpZ';
    const INCREMENT_ID = '0000000001';
    const ORDER_ID = '23';
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var AlmaClient
     */
    private $almaClient;
    /**
     * @var Processor
     */
    private $paymentProcessor;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;
    /**
     * @var BuilderInterface
     */
    private $builderInterface;
    /**
     * @var OrderHelper
     */
    private $orderHelper;
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->checkoutSession = $this->createMock(Session::class);
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->paymentProcessor = $this->createMock(Processor::class);
        $this->quoteRepository = $this->createMock(QuoteRepository::class);
        $this->builderInterface = $this->createMock(BuilderInterface::class);
        $this->orderHelper = $this->createMock(OrderHelper::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);
    }

    /**
     * @dataProvider paymentDataProvider
     */
    #[DataProvider('paymentDataProvider')]
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

        $this->builderInterface->expects($this->once())->method('setPayment')
            ->willReturnSelf();
        $this->builderInterface->expects($this->once())->method('setOrder')
            ->willReturnSelf();
        $this->builderInterface->expects($this->once())->method('setTransactionId')
            ->willReturnSelf();
        $this->builderInterface->expects($this->once())->method('setAdditionalInformation')
            ->willReturnSelf();
        $this->builderInterface->expects($this->once())->method('setFailSafe')
            ->with(true)
            ->willReturnSelf();
        $this->builderInterface->expects($this->once())->method('build')
            ->with(TransactionInterface::TYPE_PAYMENT)
            ->willReturn($this->createMock(TransactionInterface::class));
        $paymentValidation = $this->getPaymentValidationMockPartial();
        $paymentValidation->expects($this->once())->method('createPaymentData')->willReturn([]);
        $paymentValidation->expects($this->once())->method('addTransactionComment')
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
            ->method('save')
            ->with($orderMock);
        $this->orderHelper->expects($this->once())
            ->method('cancel')
            ->with($orderId);

        $paymentValidation = $this->getPaymentValidationMockPartial();
        $paymentValidation->expects($this->once())->method('addCommentToOrder')
            ->with($orderMock, 'internal error render', Order::STATUS_FRAUD)
            ->willReturn($orderMock);

        $phraseMock = $this->createMock(Phrase::class);
        $phraseMock->expects($this->exactly(2))
            ->method('render')
            ->willReturn('internal error render');
        $paymentValidation->cancelOrder($phraseMock, true, $orderMock);
    }

    /**
     * @throws AlmaPaymentValidationException
     */
    public function testGetPaymentReturnPayment(): void
    {
        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getPayment')->willReturn($paymentMock);
        $paymentValidationMock = $this->getPaymentValidationMockPartial();
        $this->assertEquals($paymentMock, $paymentValidationMock->getPayment($orderMock));
    }

    /**
     * @throws AlmaPaymentValidationException
     */
    public function testGetPaymentThrowAlmaPaymentValidationException(): void
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getPayment')->willReturn(null);
        $orderMock->method('getIncrementId')->willReturn('0000001');
        $paymentValidationMock = $this->getPaymentValidationMockPartial();
        $paymentValidationMock->method('addCommentToOrder')->willReturn($orderMock);
        $this->expectException(AlmaPaymentValidationException::class);
        $paymentValidationMock->getPayment($orderMock);
    }

    public function testIpnWithExpiredAtPropertyCancelOrder()
    {
        $this->createAlmaPaymentMock('123345');
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getIncrementId')->willReturn(self::INCREMENT_ID);
        $orderMock->method('getId')->willReturn(self::ORDER_ID);
        $this->orderHelper->method('getOrder')->willReturn($orderMock);

        $this->orderHelper->expects($this->once())->method('cancel')->with(self::ORDER_ID);

        $paymentValidation = $this->createNewPaymentValidation();
        $this->assertTrue($paymentValidation->completeOrderIfValid(self::PAYMENT_ID));
    }

    public function testIpnWithExpiredAtPropertyNullWithBadOrderThrowException(): void
    {
        $this->createAlmaPaymentMock(null);

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getIncrementId')->willReturn(self::INCREMENT_ID);
        $orderMock->method('getId')->willReturn(self::ORDER_ID);
        $this->orderHelper->method('getOrder')->willReturn($orderMock);

        $this->expectException(AlmaPaymentValidationException::class);
        $paymentValidation = $this->createNewPaymentValidation();
        $paymentValidation->completeOrderIfValid(self::PAYMENT_ID);
    }

    /**
     * @param string|null $expireAt
     *
     * @return void
     */
    private function createAlmaPaymentMock(?string $expireAt): void
    {
        $paymentMock = $this->createMock(Payment::class);
        $paymentMock->expired_at = $expireAt;
        $paymentMock->custom_data['order_id'] = self::INCREMENT_ID;

        $paymentsEndpointMock = $this->createMock(Payments::class);
        $paymentsEndpointMock->method('fetch')->willReturn($paymentMock);

        $almaClientMock = $this->createMock(Client::class);
        $almaClientMock->payments = $paymentsEndpointMock;
        $this->almaClient->method('getDefaultClient')->willReturn($almaClientMock);
    }

    private function createNewPaymentValidation(): PaymentValidation
    {
        return new PaymentValidation(...$this->getConstructorDependency());
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

    public static function paymentDataProvider(): array
    {
        return [
            'Check with deferred Trigger True' => [
                [
                    'created' => self::FIXED_TIMESTAMP,
                    'deferred_days' => self::DEFFERED_DAYS_30,
                    'deferred_trigger' => true,
                    'deferred_trigger_description' => 'At shipping'
                ],
                [
                    'total' => self::TXT_PRICE,
                    'created' => self::FIXED_TIMESTAMP,
                    'deferred_days' => self::DEFFERED_DAYS_30,
                    'installments_count' => '0',
                    'deferred_months' => '0',
                    'deferred_trigger' => 'yes',
                    'deferred_trigger_description' => 'At shipping'
                ]
            ],
            'Check with deferred Trigger False' => [
                [
                    'created' => self::FIXED_TIMESTAMP,
                    'deferred_days' => self::DEFFERED_DAYS_30,
                    'deferred_trigger' => false,
                    'deferred_trigger_description' => ''
                ],
                [
                    'total' => self::TXT_PRICE,
                    'created' => self::FIXED_TIMESTAMP,
                    'deferred_days' => self::DEFFERED_DAYS_30,
                    'installments_count' => '0',
                    'deferred_months' => '0',
                    'deferred_trigger' => 'no',
                    'deferred_trigger_description' => ''
                ]
            ]
        ];
    }

    /**
     * @return PaymentValidation|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPaymentValidationMockPartial()
    {
        return $this->getMockBuilder(PaymentValidation::class)
            ->setConstructorArgs($this->getConstructorDependency())
            ->onlyMethods(['createPaymentData', 'addTransactionComment', 'addCommentToOrder'])
            ->getMock();
    }
}
