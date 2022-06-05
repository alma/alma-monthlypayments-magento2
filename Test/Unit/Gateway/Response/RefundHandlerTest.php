<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Response\RefundHandler;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Payment\Gateway\Response\HandlerInterface;
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
        $this->assertInstanceOf(HandlerInterface::class, $this->createNewRefundDataBuilder());
    }

    private function createNewRefundDataBuilder(): RefundHandler
    {
        return new RefundHandler(...$this->getConstructorDependency());
    }

    private function getConstructorDependency(): array
    {
        return [
            $this->logger,
            $this->transactionBuilder
        ];
    }
}
