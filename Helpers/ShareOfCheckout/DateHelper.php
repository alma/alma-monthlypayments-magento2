<?php

namespace Alma\MonthlyPayments\Helpers\ShareOfCheckout;

use Magento\Framework\App\Helper\AbstractHelper;

class DateHelper extends AbstractHelper
{
    /**
     * @var string
     */
    private $endTime;
    /**
     * @var string
     */
    private $startTime;

    /**
     *
     * @param string $from
     * @param string $enabledDate
     * @param string|null $to
     *
     * @return array
     */
    public function getDatesInInterval(string $enabledDate, string $from, string $to = ''): array
    {
        $datesInInterval = [];
        if ($enabledDate == '' || $from === '') {
            return $datesInInterval;
        }
        if ($to === '') {
            $to = date('Y-m-d');
        }
        $startTimestamp =  $from;

        while ($startTimestamp < $to) {
            if ($startTimestamp > $enabledDate) {
                $datesInInterval[] = $startTimestamp;
            }
            $startTimestamp = $this->dateMoreOneDay($startTimestamp);
        }
        return $datesInInterval;
    }

    /**
     * @param string $date
     *
     * @return string
     */
    private function dateMoreOneDay(string $date): string
    {
        return date('Y-m-d', strtotime('+1 day', strtotime($date)));
    }


    /**
     * @param $date
     *
     * @return void
     */
    public function setShareDates($date): void
    {
        $this->startTime = $date . ' 00:00:00';
        $this->endTime   = $date . ' 23:59:59';
    }

    /**
     * @return string
     */
    public function getStartDate(): string
    {
        return $this->startTime;
    }
    /**
     * @return string
     */
    public function getEndDate(): string
    {
        return $this->endTime;
    }
}
