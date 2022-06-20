<?php

namespace Alma\MonthlyPayments\Test\Unit\Cron;

use Alma\MonthlyPayments\Cron\ShareOfCheckout;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\DateHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\ShareOfCheckoutHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShareOfCheckoutTest extends TestCase
{
    /**
     * @var MockObject|Logger
     */
    private $logger;
    /**
     * @var ShareOfCheckoutHelper|MockObject
     */
    private $shareOfCheckoutHelper;
    /**
     * @var DateHelper|MockObject
     */
    private $dateHelper;
    /**
     * @var ShareOfCheckout
     */
    private $shareOfCheckoutCron;

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
        $this->shareOfCheckoutHelper->expects($this->never())
            ->method('getShareOfCheckoutEnabledDate');
        $this->shareOfCheckoutCron->shareDays();
    }
    public function testShareDaysWithShareEnable()
    {
        $shareOfCheckoutEnabledDate = '2022-04-24';
        $lastUpdateDate = '2022-04-20';

        $this->shareOfCheckoutHelper->expects($this->once())
            ->method('shareOfCheckoutIsEnabled')
            ->willReturn(true);
        $this->shareOfCheckoutHelper->expects($this->once())
            ->method('getShareOfCheckoutEnabledDate')
            ->willReturn($shareOfCheckoutEnabledDate);
        $this->shareOfCheckoutHelper->expects($this->once())
            ->method('getLastUpdateDate')
            ->willReturn($lastUpdateDate);
        $this->shareOfCheckoutCron->shareDays();
    }
}
