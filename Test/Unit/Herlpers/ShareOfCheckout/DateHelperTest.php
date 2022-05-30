<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Helpers\ShareOfCheckout\DateHelper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use PHPUnit\Framework\TestCase;

class DateHelperTest extends TestCase
{
    private $dateHelper;

    public function setUp(): void
    {
        $context = $this->createMock(Context::class);
        $this->dateHelper = new DateHelper($context);
    }

    public function testInstanceDateHelper(): void
    {
        $this->assertInstanceOf(DateHelper::class, $this->dateHelper);
    }

    public function testImplementAbstractHelperInterface(): void
    {
        $this->assertInstanceOf(AbstractHelper::class, $this->dateHelper);
    }

    public function testGetDateIntervalShouldReturnAnArray(): void
    {
        $activationDate = '2022-03-01';
        $from = '';
        $to = '';
        $this->assertIsArray($this->dateHelper->getDatesInInterval($activationDate, $from, $to));
    }

    public function testGetDateIntervalShouldReturnDay02(): void
    {
        $activationDate = '2022-03-01';
        $from = '2022-04-01';
        $to = '2022-04-02';
        $this->assertEquals(['2022-04-01'], $this->dateHelper->getDatesInInterval($activationDate, $from, $to));
        ;
    }
    public function testGetDateIntervalShouldReturnDay03(): void
    {
        $activationDate = '2022-03-01';
        $from = '2022-04-02';
        $to = '2022-04-03';
        $this->assertEquals(['2022-04-02'], $this->dateHelper->getDatesInInterval($activationDate, $from, $to));
        ;
    }
    public function testGetDateIntervalShouldReturnDay02And03(): void
    {
        $activationDate = '2022-03-01';
        $from = '2022-04-01';
        $to = '2022-04-03';
        $this->assertEquals(['2022-04-01','2022-04-02'], $this->dateHelper->getDatesInInterval($activationDate, $from, $to));
    }
    public function testGetDateIntervalShouldReturnDay02And03And04(): void
    {
        $activationDate = '2022-03-01';
        $from = '2022-04-01';
        $to = '2022-04-04';
        $this->assertEquals(['2022-04-01','2022-04-02','2022-04-03'], $this->dateHelper->getDatesInInterval($activationDate, $from, $to));
    }
    public function testGetDateIntervalShouldNotReturnDateBeforeActivationDate(): void
    {
        $activationDate = '2022-04-29';
        $from = '2022-04-25';
        $to = '2022-05-03';
        $this->assertEquals(['2022-04-30','2022-05-01','2022-05-02'], $this->dateHelper->getDatesInInterval($activationDate, $from, $to));
    }
    public function testGetDateIntervalShouldUseYesterdayDateIdToIsUndefined(): void
    {
        $activationDate = '2020-01-01';
        $from = date('Y-m-d', strtotime('-4 day', time()));
        $to = '';
        $this->assertEquals([date('Y-m-d', strtotime('-4 day', time())),date('Y-m-d', strtotime('-3 day', time())),date('Y-m-d', strtotime('-2 day', time())),date('Y-m-d', strtotime('-1 day', time()))], $this->dateHelper->getDatesInInterval($activationDate, $from, $to));
    }
    public function testGetDateIntervalShouldReturnVoidArrayWithoutActivationDate(): void
    {
        $activationDate = '';
        $from = '2022-04-25';
        $to = '2022-05-02';
        $this->assertEquals([], $this->dateHelper->getDatesInInterval($activationDate, $from, $to));
    }
    public function testGetDateIntervalShouldReturnVoidArrayWithoutFromDate(): void
    {
        $activationDate = '2022-01-01';
        $from = '';
        $to = '2022-05-02';
        $this->assertEquals([], $this->dateHelper->getDatesInInterval($activationDate, $from, $to));
    }

}
