<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Validator;

use Alma\API\Entities\Payment;
use Alma\API\Response;
use Alma\MonthlyPayments\Gateway\Validator\RefundResponseValidator;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Mockery;
use PHPUnit\Framework\TestCase;

class RefundResponseValidatorTest extends TestCase
{
    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->resultInterface = $this->createMock(ResultInterfaceFactory::class);
    }

    public function testRefundResponseValidatorIsInstanceOffAbstractValidator(): void
    {
        $this->assertInstanceOf(AbstractValidator::class, $this->createNewRefundDataBuilder());
    }

    private function createNewRefundDataBuilder(): RefundResponseValidator
    {
        return new RefundResponseValidator(...$this->getConstructorDependency());
    }

    /**
     * @dataProvider errorDataProvider
     */
    public function testCreateResultErrorParams($responseCode, $json, $failsDescription): void
    {
        $almaApiResponseError = Mockery::mock(Response::class);
        $almaApiResponseError->responseCode = $responseCode;
        $almaApiResponseError->json = $json;
        $validationSubjectMock = [
            'response' => [
                'resultCode' => 0,
                'fails' => $almaApiResponseError
            ],
        ];

        $resultInterface = Mockery::mock(ResultInterface::class);

        $refundResponseValidatorMock = Mockery::mock(RefundResponseValidator::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $refundResponseValidatorMock->shouldReceive('createResult')
            ->once()
            ->with(0, [$failsDescription], [$almaApiResponseError->responseCode])
            ->andReturn($resultInterface);
        $this->assertEquals($resultInterface, $refundResponseValidatorMock->validate($validationSubjectMock));
    }

    public function testCreateResultSuccess(): void
    {
        $almaPaymentMock = Mockery::mock(Payment::class);
        $validationSubjectMock = [
            'response' => [
                'resultCode' => 1,
                'almaRefund' => $almaPaymentMock
            ],
        ];
        $resultInterfaceMock = Mockery::mock(ResultInterface::class);
        $refundResponseValidatorMock = Mockery::mock(RefundResponseValidator::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $refundResponseValidatorMock->shouldReceive('createResult')
            ->with(1, [], [])
            ->andReturn(Mockery::mock(ResultInterface::class));
        $this->assertEquals($resultInterfaceMock, $refundResponseValidatorMock->validate($validationSubjectMock));
    }

    public function errorDataProvider(): array
    {
        return [
            'Test error 404' => [
                'responseCode' => 404,
                'json' => [
                    'error_code' => 'not_found',
                    'object_type' => 'Payment',
                    'value' => 'payment_11ujT8U2zSX9OUlFbRVUBcHuTU2191NrYQ123',
                ],
                'failsDescription' => 'Payment payment_11ujT8U2zSX9OUlFbRVUBcHuTU2191NrYQ123 not_found'
             ],
            'Test error 400' => [
                'responseCode' => 400,
                'json' => [
                    'error_code' => 'validation_error',
                    'errors' => [
                       [ 'error_code' => 'invalid_value',
                        'field' => 'amount',
                        'message' => "Impossible de rembourser plus que le montant de l'achat",
                        'value' => 100000000
                       ]
                    ],
                ],
                'failsDescription' => "validation_error : Impossible de rembourser plus que le montant de l'achat"
             ]
        ];
    }

    private function getConstructorDependency(): array
    {
        return [
            $this->resultInterface
        ];
    }
}
