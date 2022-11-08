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
    private $socHelper;
    /**
     * @var DateHelper
     */
    private $dateHelper;

    /**
     * @param Logger $logger
     * @param SOCHelper $socHelper
     * @param DateHelper $dateHelper
     */
    public function __construct(
        Logger     $logger,
        SOCHelper  $socHelper,
        DateHelper $dateHelper
    ) {
        $this->logger = $logger;
        $this->socHelper = $socHelper;
        $this->dateHelper = $dateHelper;
    }

    /**
     *
     * @return void
     */
    public function shareDays(): void
    {
        if (!$this->socHelper->isEnabled()) {
            return;
        }

        try {
            $enabledDate = $this->socHelper->getEnabledDate();
            $lastUpdateDate = $this->socHelper->getLastUpdateDate();
        } catch (RequestError $e) {
            $this->logger->info('Get Last Update Date error - end of process - message : ', [$e->getMessage()]);
            return;
        }

        $datesToShare = $this->dateHelper->getDatesInInterval($lastUpdateDate, $enabledDate);
        foreach ($datesToShare as $date) {
            try {
                $this->socHelper->shareDay($date);
            } catch (RequestError $e) {
                $this->logger->info('Share of checkout error - end of process - message : ', [$e->getMessage()]);
                return;
            }
        }
    }
}
