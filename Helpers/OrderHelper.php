<?php

namespace Alma\MonthlyPayments\Helpers;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderFactory;

class OrderHelper extends AbstractHelper
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    public function __construct(
        Context $context,
        OrderFactory $orderFactory
    )
    {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param int $orderId
     * @return OrderInterface
     */
    public function getOrderById($orderId):OrderInterface
    {
        $orderModel = $this->orderFactory->create();
        return $orderModel->loadByIncrementId($orderId);
    }
}
