<?php

namespace Alma\MonthlyPayments\Model\Exceptions;

use Exception;
use PHPUnit\Framework\TestCase;

class AlmaPaymentValidationErrorTest extends TestCase
{
    public function setUp(): void
    {
        $this->paymentValidationError = new AlmaPaymentValidationException();
    }

    public function tearDown(): void
    {
        $this->paymentValidationError = null;
    }

    public function testPaymentValidationErrorExtendsException(): void
    {
        $this->assertInstanceOf(Exception::class, $this->paymentValidationError);
    }

    public function testDefaultMessageIsEmpty(): void
    {
        $this->assertEquals('', $this->paymentValidationError->getMessage());
    }

    public function testCustomMessage(): void
    {
        $customMessage = 'Custom Message';
        $paymentValidationError = new AlmaPaymentValidationException($customMessage);
        $this->assertEquals($customMessage, $paymentValidationError->getMessage());
    }

    public function testDefaultFailureUrl(): void
    {
        $this->assertEquals(AlmaPaymentValidationException::RETURN_PATH, $this->paymentValidationError->getReturnPath());
    }

    public function testCustomFailureUrl(): void
    {
        $customFailureUrl = 'custom/failure/url';
        $paymentValidationError = new AlmaPaymentValidationException('', $customFailureUrl);
        $this->assertEquals($customFailureUrl, $paymentValidationError->getReturnPath());
    }
}
