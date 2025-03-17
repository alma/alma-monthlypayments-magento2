<?php

namespace Alma\MonthlyPayments\Observer;

use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Exceptions\MerchantBusinessServiceException;
use Alma\MonthlyPayments\Services\MerchantBusinessService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Invoice;

class SalesOrderInvoicePayObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var MerchantBusinessService
     */
    private $merchantBusinessService;

    public function __construct(
        Logger                  $logger,
        MerchantBusinessService $merchantBusinessService
    ) {
        $this->logger = $logger;
        $this->merchantBusinessService = $merchantBusinessService;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var Invoice $invoice */
        $invoice = $observer->getEvent()->getData('invoice');

        try {
            $orderConfirmedBusinessEventDTO = $this->merchantBusinessService->createOrderConfirmedBusinessEventByOrder($invoice->getOrder());
            $this->merchantBusinessService->sendOrderConfirmedBusinessEvent($orderConfirmedBusinessEventDTO);
        } catch (MerchantBusinessServiceException $e) {
            $this->logger->error('Error sending Order Confirmed Business Event observer', ['exception' => $e]);
        }
    }
}
