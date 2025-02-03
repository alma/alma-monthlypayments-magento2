<?php

namespace Alma\MonthlyPayments\Observer;


use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Services\MerchantBusinessService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;


class SalesQuoteSaveAfterObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var MerchantBusinessService
     */
    private $merchantBusinessService;

    /**
     * @param Logger $logger
     * @param MerchantBusinessService $merchantBusinessService
     */
    public function __construct(
        Logger                  $logger,
        MerchantBusinessService $merchantBusinessService
    )
    {
        $this->logger = $logger;
        $this->merchantBusinessService = $merchantBusinessService;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getData('quote');
        if (!$this->merchantBusinessService->isSendCartInitiatedNotification($quote)) {
            $this->merchantBusinessService->createAndSendCartInitiatedBusinessEvent($quote);
        }
    }


}
