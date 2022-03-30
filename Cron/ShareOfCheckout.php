<?php

namespace Alma\MonthlyPayments\Cron;

use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
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
     * @var ConfigHelper
     */
    private $configHelper;


    /**
     * @param Logger $logger
     * @param ShareOfCheckoutHelper $shareOfCheckoutHelper
     */
    public function __construct(
        Logger $logger,
        ShareOfCheckoutHelper $shareOfCheckoutHelper,
        DateHelper $dateHelper,
        ConfigHelper $configHelper
    )
    {
        $this->logger = $logger;
        $this->shareOfCheckoutHelper = $shareOfCheckoutHelper;
        $this->dateHelper = $dateHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * @throws RequestError
     */
    public function shareDays():void
    {
        ini_set('max_execution_time', 30);
        if(!$this->configHelper->shareOfCheckoutIsEnabled()){
            $this->logger->info('Share Of Checkout is not enabled',[]);
            return;
        }
        $lastUpdateDate = $this->shareOfCheckoutHelper->getLastUpdateDate();
        $DatesToShare = $this->dateHelper->getDatesInInterval($lastUpdateDate);
        foreach ($DatesToShare as $date) {
            try {
                $this->shareOfCheckoutHelper->setShareOfCheckoutFromDate($date);
                $this->shareOfCheckoutHelper->shareDay();
            } catch (RequestError $e) {
                //throw new RequestError($e->getMessage(), null, null);
            }
        }
    }


}
