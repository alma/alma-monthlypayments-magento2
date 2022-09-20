<?php

namespace Alma\MonthlyPayments\Cron;

use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\DateHelper;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\SOCHelper;

class SOCShareCron
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var SOCHelper
     */
    private $SOCHelper;
    /**
     * @var DateHelper
     */
    private $dateHelper;

    /**
     * @param Logger $logger
     * @param SOCHelper $SOCHelper
     * @param DateHelper $dateHelper
     */
    public function __construct(
        Logger     $logger,
        SOCHelper  $SOCHelper,
        DateHelper $dateHelper
    ) {
        $this->logger = $logger;
        $this->SOCHelper = $SOCHelper;
        $this->dateHelper = $dateHelper;
    }

    /**
     *
     * @return void
     */
    public function shareDays(): void
    {
        if (!$this->SOCHelper->isEnabled()) {
            return ;
        }

        try {
            $enabledDate = $this->SOCHelper->getEnabledDate();
            $lastUpdateDate = $this->SOCHelper->getLastUpdateDate();
        } catch (RequestError $e) {
            $this->logger->info('Get Last Update Date error - end of process - message : ', [$e->getMessage()]);
            return;
        }

        $datesToShare = $this->dateHelper->getDatesInInterval($lastUpdateDate, $enabledDate);
        foreach ($datesToShare as $date) {
            try {
                $this->SOCHelper->shareDay($date);
            } catch (RequestError $e) {
                $this->logger->info('Share of checkout error - end of process - message : ', [$e->getMessage()]);
                return;
            }
        }
    }
}
