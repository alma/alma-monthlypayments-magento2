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
    public function getDataIntervalProvider(): array
    {
        $now = time();
        return [
            'should return only 1 day' => [
                '2022-01-01',
                '2022-04-02',
                '2022-04-03',
                ['2022-04-02']
            ],
            'should return various days' => [
                '2022-01-01',
                '2022-04-01',
                '2022-04-04',
                ['2022-04-01','2022-04-02','2022-04-03']
            ],
            'should not be return days before activation day' => [
                '2022-04-29',
                '2022-04-01',
                '2022-05-03',
                ['2022-04-30','2022-05-01','2022-05-02']
            ],
            'should use yesterday if to date is empty' => [
                '2022-01-01',
                date('Y-m-d', strtotime('-3 day', $now)),
                '',
                [date('Y-m-d', strtotime('-3 day', $now)),date('Y-m-d', strtotime('-2 day', $now)),date('Y-m-d', strtotime('-1 day', $now))]
            ],
        ];
    }

    public function getErrorsDataIntervalProvider(): array
    {
        return [
            'No return with empty activation date' => [
            '',
            '2022-04-02',
            '2022-04-04'
            ],
            'No return with empty from date' => [
            '2022-01-01',
            '',
            '2022-04-04'
            ],
        ];
    }

    /**
     * @dataProvider getDataIntervalProvider
     */
    public function testGetDateInterval($activationDate, $from, $to, $expected): void
    {
        $this->assertEquals($expected, $this->dateHelper->getDatesInInterval($activationDate, $from, $to));
    }
    /**
     * @dataProvider getErrorsDataIntervalProvider
     */
    public function testGetDateIntervalErrors($activationDate, $from, $to): void
    {
        $this->assertEquals([], $this->dateHelper->getDatesInInterval($activationDate, $from, $to));
    }
}
