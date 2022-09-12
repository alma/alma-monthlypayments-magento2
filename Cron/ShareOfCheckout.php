<?php

namespace Alma\MonthlyPayments\Cron;

use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\DateHelper;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\ShareOfCheckoutHelper;

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
        if ($this->shareOfCheckoutHelper->shareOfCheckoutIsEnabled()) {
            return ;
        }

        try {
            $shareOfCheckoutEnabledDate = $this->shareOfCheckoutHelper->getShareOfCheckoutEnabledDate();
            $lastUpdateDate = $this->shareOfCheckoutHelper->getLastUpdateDate();
        } catch (RequestError $e) {
            $this->logger->info('Get Last Update Date error - end of process - message : ', [$e->getMessage()]);
            return;
        }

        $datesToShare = $this->dateHelper->getDatesInInterval($lastUpdateDate, $shareOfCheckoutEnabledDate);
        foreach ($datesToShare as $date) {
            try {
                $this->shareOfCheckoutHelper->shareDay($date);
            } catch (RequestError $e) {
                $this->logger->info('Share of checkout error - end of process - message : ', [$e->getMessage()]);
                return;
            }
        }
    }
}
