<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\API\Client;
use Alma\API\Endpoints\Payments;
use Alma\API\Entities\Payment;
use Alma\API\RequestError;
use Alma\API\Response;
use Alma\MonthlyPayments\Gateway\Http\Client\RefundClient;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Functions;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\Transfer;
use PHPUnit\Framework\TestCase;

class RefundClientTest extends TestCase
{
    public function setUp(): void
    {
        $this->mockPaymentId = 'payment_11uNKOn3uuKhgUdY2eU6AZF1oKifmetCKZ';
        $this->mockMerchantId = 'merchant_11uNKOn3uuKhgUdY2eU6AZF1oKiametCKZ';
        $this->mockAmount =  '1021.2';
        $this->logger = $this->createMock(Logger::class);
        $this->almaClient = $this->createMock(AlmaClient::class);
    }

    public function testRefundClientIsInstanceOffClientInterface(): void
    {
        $this->assertInstanceOf(ClientInterface::class, $this->createNewRefundClient());
    }

    private function createNewRefundClient(): RefundClient
    {
        return new RefundClient(...$this->getConstructorDependency());
    }

    public function testPartialRefundParams(): void
    {
        $payment = $this->createMock(Payment::class);
        $endpointPayment = $this->createMock(Payments::class);
        $clientMock = $this->createMock(Client::class);
        $this->almaClient->expects($this->once())
            ->method('getDefaultClient')
            ->willReturn($clientMock);
        $clientMock->payments = $endpointPayment;
        $sendAmount = (int)($this->mockAmount * 100);
        $endpointPayment->expects($this->once())
            ->method('partialRefund')
            ->with($this->mockPaymentId, $sendAmount, $this->mockMerchantId)
            ->willReturn($payment);
        $refundClient  = $this->createNewRefundClient();
        $this->assertEquals(
            [
                'resultCode' => 1,
                'almaRefund' => $payment,
                'isFullRefund' => false
            ],
            $refundClient->placeRequest($this->getMockTransferObject($this->mockAmount, '2000', '0'))
        );
    }

    public function testFullRefundParams(): void
    {
        $payment = $this->createMock(Payment::class);
        $endpointPayment = $this->createMock(Payments::class);
        $clientMock = $this->createMock(Client::class);
        $this->almaClient->expects($this->once())
            ->method('getDefaultClient')
            ->willReturn($clientMock);
        $clientMock->payments = $endpointPayment;
        $endpointPayment->expects($this->once())
            ->method('fullRefund')
            ->with($this->mockPaymentId, $this->mockMerchantId)
            ->willReturn($payment);
        $refundClient  = $this->createNewRefundClient();
        $this->assertEquals(
            [
                'resultCode' => 1,
                'almaRefund' => $payment,
                'isFullRefund' => true
            ],
            $refundClient->placeRequest($this->getMockTransferObject('1000', '2000', '1000'))
        );
    }

    public function testPartialRefundError(): void
    {
        $clientMock = $this->createMock(Client::class);
        $endpointPayment = $this->createMock(Payments::class);
        $responseErrorMock = $this->createMock(Response::class);
        $exception = new RequestError('Error creating refund with alma API', null, $responseErrorMock);
        $this->almaClient->expects($this->once())
            ->method('getDefaultClient')
            ->willReturn($clientMock);
        $sendAmount = (int)($this->mockAmount * 100);
        $clientMock->payments = $endpointPayment;
        $endpointPayment->expects($this->once())
            ->method('partialRefund')
            ->with($this->mockPaymentId, $sendAmount, $this->mockMerchantId)
            ->willThrowException($exception);
        $refundClient  = $this->createNewRefundClient();
        $this->assertEquals(
            [
                'resultCode' => 0,
                'fails' => $responseErrorMock
            ],
            $refundClient->placeRequest($this->getMockTransferObject($this->mockAmount, '2500', '500'))
        );
    }



    private function getMockTransferObject($amount, $orderTotal, $totalRefund): Transfer
    {
        $transferObject =  $this->createMock(Transfer::class);
        $transferObject->expects($this->once())
            ->method('getBody')
            ->willReturn(
                [
                    'payment_id' => $this->mockPaymentId,
                    'merchant_id' => $this->mockMerchantId,
                    'amount' => $amount,
                    'order_total' => $orderTotal,
                    'total_refund' => $totalRefund,
                    'store_id' => '2',
                ]
            );
        return $transferObject;
    }

    private function getConstructorDependency(): array
    {
        return [
            $this->logger,
            $this->almaClient
        ];
    }
}
