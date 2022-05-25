<?php

namespace Alma\MonthlyPayments\Cron;

use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\DateHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\ShareOfCheckoutHelper;

class ShareOfCheckout
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ShareOfCheckoutHelper
     */
    private $shareOfCheckoutHelper;
    /**
     * @var DateHelper
     */
    private $dateHelper;

    /**
     * @param Logger $logger
     * @param ShareOfCheckoutHelper $shareOfCheckoutHelper
     * @param DateHelper $dateHelper
     */
    public function __construct(
        Logger $logger,
        ShareOfCheckoutHelper $shareOfCheckoutHelper,
        DateHelper $dateHelper
    ) {
        $this->logger = $logger;
        $this->shareOfCheckoutHelper = $shareOfCheckoutHelper;
        $this->dateHelper = $dateHelper;
    }

    /**
     *
     * @return void
     */
    public function shareDays(): void
    {
        ini_set('max_execution_time', 30);

        if (!$this->shareOfCheckoutHelper->shareOfCheckoutIsEnabled()) {
            return;
        }

        try {
            $shareOfCheckoutEnabledDate = $this->shareOfCheckoutHelper->getShareOfCheckoutEnabledDate();
            $lastUpdateDate = $this->shareOfCheckoutHelper->getLastUpdateDate();
        } catch (RequestError $e) {
            $this->logger->info('Get Last Update Date error - end of process - message : ', [$e->getMessage()]);
            return;
        }

        $DatesToShare = $this->dateHelper->getDatesInInterval($lastUpdateDate, $shareOfCheckoutEnabledDate);
        foreach ($DatesToShare as $date) {
            try {
                $this->shareOfCheckoutHelper->setShareOfCheckoutFromDate($date);
                $this->shareOfCheckoutHelper->shareDay();
            } catch (RequestError $e) {
                $this->logger->info('Share of checkout error - end of process - message : ', [$e->getMessage()]);
                return;
            }
        }
    }
}
