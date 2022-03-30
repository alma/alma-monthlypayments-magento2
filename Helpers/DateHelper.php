<?php

namespace Alma\MonthlyPayments\Helpers;

class DateHelper
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Logger $logger
    )
    {
        $this->logger = $logger;
    }
    /**
     * @param string $from
     * @param string $to
     * @return array
     */
    public function getDatesInInterval($from,$shareOfCheckoutEnabledDate,$to = null):array
    {
        if(!isset($to)){
            $to = strtotime('-1 day');
        }
        $datesInInterval = [];
        $startTimestamp = strtotime('+1 day',strtotime($from));
        for ($i = $startTimestamp; $i <= $to ; $i = strtotime('+1 day', $i)) {
            if($i > strtotime($shareOfCheckoutEnabledDate)){
                $datesInInterval[] = date('Y-m-d',$i);
            }
        }
        return $datesInInterval;
    }

}
