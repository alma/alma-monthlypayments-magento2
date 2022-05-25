<?php

namespace Alma\MonthlyPayments\Helpers;

class DateHelper
{
    /**
     * @param string $from
     * @param string $shareOfCheckoutEnabledDate
     * @param string|null $to
     * @return array
     */
    public function getDatesInInterval($from, $shareOfCheckoutEnabledDate, $to = null):array
    {
        if(!isset($to)){
            $to = strtotime('-1 day');
        }
        $datesInInterval = [];
        $startTimestamp = strtotime('+1 day', strtotime($from));
        for ($i = $startTimestamp; $i <= $to ; $i = strtotime('+1 day', $i)) {
            if($i >= strtotime($shareOfCheckoutEnabledDate)){
                $datesInInterval[] = date('Y-m-d', $i);
            }
        }
        return $datesInInterval;
    }

}
