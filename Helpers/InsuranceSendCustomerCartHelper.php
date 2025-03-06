<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order\Invoice\Item;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Item\Collection;

class InsuranceSendCustomerCartHelper extends AbstractHelper
{
    private $almaClient;
    private $logger;

    public function __construct(
        Context    $context,
        AlmaClient $almaClient,
        Logger     $logger
    ) {
        parent::__construct($context);
        $this->almaClient = $almaClient;
        $this->logger = $logger;
    }

    /**
     * @param Collection $invoicesItemsCollection
     * @param int $quoteId
     * @return void
     */
    public function sendCustomerCart(Collection $invoicesItemsCollection, int $orderId)
    {
        $items = [];
        /** @var Item $item */
        foreach ($invoicesItemsCollection as $item) {
            if ($item->getOrderItem()->getParentItemId()) {
                continue;
            }
            $sku = $item->getSku();
            if ($sku !== InsuranceHelper::ALMA_INSURANCE_SKU) {
                for ($i = 0; $i < (int)$item->getQty(); $i++) {
                    $items[] = $sku;
                }
            }
        }
        try {
            $this->almaClient->getDefaultClient()->insurance->sendCustomerCart($items, $orderId);
        } catch (RequestError|AlmaClientException $e) {
            $this->logger->error('Error sending customer cart to Alma', ['exception' => $e]);
        }
    }
}
