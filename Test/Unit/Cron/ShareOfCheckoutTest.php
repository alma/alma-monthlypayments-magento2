<?php

namespace Alma\MonthlyPayments\Test\Unit\Cron;

use Alma\MonthlyPayments\Cron\ShareOfCheckout;
use Alma\MonthlyPayments\Helpers\DateHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\ShareOfCheckoutHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShareOfCheckoutTest extends TestCase
{
    /**
     * @var MockObject|Logger
     */
    private $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->shareOfCheckoutHelper = $this->createMock(ShareOfCheckoutHelper::class);
        $this->dateHelper = $this->createMock(DateHelper::class);
        $this->shareOfCheckoutCron = new ShareOfCheckout(
            $this->logger,
            $this->shareOfCheckoutHelper,
            $this->dateHelper
        );
    }

    public function testInstanceShareOfCheckoutCron(): void
    {
        $this->assertInstanceOf(ShareOfCheckout::class, $this->shareOfCheckoutCron);
    }
    public function testShareDaysReturnIfDisable()
    {
        $this->shareOfCheckoutHelper->expects($this->once())
            ->method('shareOfCheckoutIsEnabled')
            ->willReturn(false);
    }
}
