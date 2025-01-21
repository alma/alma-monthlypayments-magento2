<?php

namespace Alma\MonthlyPayments\Test\Unit\Services;

use Alma\API\Client;
use Alma\API\Endpoints\Merchants;
use Alma\API\Entities\DTO\MerchantBusinessEvent\OrderConfirmedBusinessEvent;
use Alma\API\Exceptions\RequestException;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Exceptions\MerchantBusinessServiceException;
use Alma\MonthlyPayments\Services\MerchantBusinessService;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;

class MerchantBusinessServiceTest extends TestCase
{
    private $almaClient;
    private $logger;
    private $merchantEndpoint;
    private $merchantBusinessService;
    private $quoteRepository;

    public function setUp(): void
    {
        $this->merchantEndpoint = $this->createMock(Merchants::class);
        $client = $this->createMock(Client::class);
        $client->merchants = $this->merchantEndpoint;
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->almaClient->method('getDefaultClient')->willReturn($client);
        $this->logger = $this->createMock(Logger::class);
        $this->quoteRepository = $this->createMock(QuoteRepository::class);

        $this->merchantBusinessService = new MerchantBusinessService(
            $this->almaClient,
            $this->logger,
            $this->quoteRepository
        );
    }


    public function testSendOrderConfirmedCallPhpClient()
    {
        $orderConfirmedMock = $this->createMock(OrderConfirmedBusinessEvent::class);
        $this->merchantEndpoint
            ->expects($this->once())
            ->method('sendOrderConfirmedBusinessEvent')
            ->with($orderConfirmedMock);
        $this->assertNull($this->merchantBusinessService->sendOrderConfirmedBusinessEvent($orderConfirmedMock));
    }

    public function testSendOrderConfirmedLogErrorButNotThrowException()
    {
        $orderConfirmedMock = $this->createMock(OrderConfirmedBusinessEvent::class);
        $this->merchantEndpoint
            ->expects($this->once())
            ->method('sendOrderConfirmedBusinessEvent')
            ->with($orderConfirmedMock)
            ->willThrowException(new RequestException('Error in send'));
        $this->logger
            ->expects($this->once())
            ->method('error');
        $this->assertNull($this->merchantBusinessService->sendOrderConfirmedBusinessEvent($orderConfirmedMock));
    }

    public function testCreateOrderConfirmedWithNonAlmaPaymentReturnOrderConfirmedObject()
    {
        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $paymentMock->method('getAdditionalInformation')
            ->willReturn([]);
        $quote = $this->createMock(Quote::class);
        $orderMock = $this->createMock(Order::class);
        $orderMock
            ->method('getPayment')
            ->willReturn($paymentMock);
        $orderMock
            ->method('getId')
            ->willReturn('42');
        $orderMock
            ->method('getQuoteId')
            ->willReturn('12');
        $quote
            ->method('getData')
            ->with('alma_bnpl_eligibility')
            ->willReturn(false);
        $this->quoteRepository
            ->method('get')
            ->willReturn($quote);

        $dto = $this->merchantBusinessService->createOrderConfirmedBusinessEventByOrder($orderMock);
        $this->assertInstanceOf(OrderConfirmedBusinessEvent::class, $dto);
        $this->assertEquals(false, $dto->isAlmaP1X());
        $this->assertEquals(false, $dto->isAlmaBNPL());
        $this->assertEquals(false, $dto->wasBNPLEligible());
        $this->assertEquals('42', $dto->getOrderId());
        $this->assertEquals('12', $dto->getCartId());
        $this->assertNull($dto->getAlmaPaymentId());
    }

    public function testCreateOrderConfirmedWithAlmaPaymentReturnOrderConfirmedObject()
    {
        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $paymentMock->method('getAdditionalInformation')
            ->willReturn(['PAYMENT_ID' => 'alma_payment_external_id', 'selectedPlan' => 'general:1:15:0']);
        $quote = $this->createMock(Quote::class);

        $orderMock = $this->createMock(Order::class);
        $orderMock
            ->method('getPayment')
            ->willReturn($paymentMock);
        $orderMock
            ->method('getId')
            ->willReturn('42');
        $orderMock
            ->method('getQuoteId')
            ->willReturn('12');
        $quote
            ->method('getData')
            ->with('alma_bnpl_eligibility')
            ->willReturn(true);
        $this->quoteRepository
            ->method('get')
            ->willReturn($quote);

        $dto = $this->merchantBusinessService->createOrderConfirmedBusinessEventByOrder($orderMock);
        $this->assertInstanceOf(OrderConfirmedBusinessEvent::class, $dto);
        $this->assertEquals(false, $dto->isAlmaP1X());
        $this->assertEquals(true, $dto->isAlmaBNPL());
        $this->assertEquals(true, $dto->wasBNPLEligible());
        $this->assertEquals('42', $dto->getOrderId());
        $this->assertEquals('12', $dto->getCartId());
        $this->assertEquals('alma_payment_external_id', $dto->getAlmaPaymentId());
    }

    public function testCreateOrderConfirmedWithAlmaPayNowReturnOrderConfirmedObject()
    {
        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $paymentMock->method('getAdditionalInformation')
            ->willReturn(['PAYMENT_ID' => 'alma_payment_external_id', 'selectedPlan' => 'general:1:0:0']);
        $quote = $this->createMock(Quote::class);

        $orderMock = $this->createMock(Order::class);
        $orderMock
            ->method('getPayment')
            ->willReturn($paymentMock);
        $orderMock
            ->method('getId')
            ->willReturn('42');
        $orderMock
            ->method('getQuoteId')
            ->willReturn('12');
        $quote
            ->method('getData')
            ->with('alma_bnpl_eligibility')
            ->willReturn(true);
        $this->quoteRepository
            ->method('get')
            ->willReturn($quote);

        $dto = $this->merchantBusinessService->createOrderConfirmedBusinessEventByOrder($orderMock);
        $this->assertInstanceOf(OrderConfirmedBusinessEvent::class, $dto);
        $this->assertEquals(true, $dto->isAlmaP1X());
        $this->assertEquals(false, $dto->isAlmaBNPL());
        $this->assertEquals(true, $dto->wasBNPLEligible());
        $this->assertEquals('42', $dto->getOrderId());
        $this->assertEquals('12', $dto->getCartId());
        $this->assertEquals('alma_payment_external_id', $dto->getAlmaPaymentId());
    }

    public function testCreateOrderConfirmedThrowExceptionWhenQuoteNotFound()
    {
        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $paymentMock->method('getAdditionalInformation')
            ->willReturn(['PAYMENT_ID' => 'alma_payment_external_id', 'selectedPlan' => 'general:1:0:0']);
        $orderMock = $this->createMock(Order::class);
        $orderMock
            ->method('getPayment')
            ->willReturn($paymentMock);
        $orderMock
            ->method('getQuoteId')
            ->willReturn('12');
        $this->quoteRepository
            ->method('get')
            ->willThrowException(new NoSuchEntityException());
        $this->expectException(MerchantBusinessServiceException::class);
        $this->merchantBusinessService->createOrderConfirmedBusinessEventByOrder($orderMock);
    }

    public function testCreateOrderConfirmedThrowExceptionForBadDataTypeInConstruct()
    {
        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $paymentMock->method('getAdditionalInformation')
            ->willReturn(['PAYMENT_ID' => 'alma_payment_external_id', 'selectedPlan' => 'general:1:0:0']);
        $quote = $this->createMock(Quote::class);

        $orderMock = $this->createMock(Order::class);
        $orderMock
            ->method('getPayment')
            ->willReturn($paymentMock);
        $orderMock
            ->method('getId')
            ->willReturn(42);
        $orderMock
            ->method('getQuoteId')
            ->willReturn(12);
        $quote
            ->method('getData')
            ->with('alma_bnpl_eligibility')
            ->willReturn(true);
        $this->quoteRepository
            ->method('get')
            ->willReturn($quote);
        $this->expectException(MerchantBusinessServiceException::class);
        $this->merchantBusinessService->createOrderConfirmedBusinessEventByOrder($orderMock);
    }

}
