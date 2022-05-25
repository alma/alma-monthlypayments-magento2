<?php

namespace Alma\MonthlyPayments\Test\Unit\Cron;

use Alma\MonthlyPayments\Cron\ShareOfCheckout;
use PHPUnit\Framework\TestCase;

class ShareOfCheckoutTest extends TestCase
{
    protected function setUp(): void
    {
        $this->shareOfCheckoutCron = new ShareOfCheckout();
    }

    public function testInstanceShareOfCheckoutCron(): void
    {
        $this->assertInstanceOf(ShareOfCheckout::class, $this->shareOfCheckoutCron);
    }
    public function testShareDaysReturnIfDisable()
    {

    }
}
