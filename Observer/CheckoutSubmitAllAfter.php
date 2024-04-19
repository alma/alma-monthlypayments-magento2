<?php

namespace Alma\MonthlyPayments\Observer;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Quote;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderModel;

class CheckoutSubmitAllAfter implements ObserverInterface
{

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var OrderModel
     */
    private $orderModel;

    public function __construct(
        Logger     $logger,
        Json       $json,
        OrderModel $orderModel
    ) {
        $this->logger = $logger;
        $this->json = $json;
        $this->orderModel = $orderModel;
    }

    /**
     * Transfer insurance data from quoteItems to orderItems
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');

        /** @var Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

        $orderItems = $order->getAllVisibleItems();
        $this->logger->info('$orderItems', [$orderItems]);

        $saveInsuranceData = false;

        /** @var Order\Item $orderItem */
        foreach ($orderItems as $orderItem) {
            $productType = $orderItem->getProductType();
            switch ($productType) {
                case 'simple_product_with_alma_insurance':
                case 'configurable_product_with_alma_insurance':
                case 'alma_insurance':
                    $saveInsuranceData = true;
                    $this->setInsuranceData($orderItem, $quote);
                    break;
                default:
                    break;
            }
        }

        if ($saveInsuranceData) {
            try {
                $this->orderModel->save($order);
            } catch (\Exception $e) {
                $this->logger->error('Impossible to save order', [$e->getMessage()]);
            }
        }
    }

    private function setInsuranceData(Order\Item $orderItem, Quote $quote): void
    {
        $quoteItemId = $orderItem->getQuoteItemId();
        $quoteItem = $quote->getItemById($quoteItemId);
        $quoteItemInsuranceData = $quoteItem->getData(InsuranceHelper::ALMA_INSURANCE_DB_KEY);
        $orderItem->setData(InsuranceHelper::ALMA_INSURANCE_DB_KEY, $quoteItemInsuranceData);
        $quoteItemInsuranceData = null;
    }
}
