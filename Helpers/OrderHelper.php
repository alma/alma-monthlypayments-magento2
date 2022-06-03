<?php

namespace Alma\MonthlyPayments\Helpers;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

class OrderHelper extends AbstractHelper
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        OrderManagementInterface $orderManagement
    ) {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->orderManagement = $orderManagement;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param string $orderId
     * @return OrderInterface
     */
    public function getOrderById(string $orderId): OrderInterface
    {
        $orderModel = $this->orderFactory->create();
        return $orderModel->loadByIncrementId($orderId);
    }

    /**
     * @param string $orderId
     *
     * @return void
     */
    public function cancelOrderById(string $orderId): void
    {
        $this->orderManagement->cancel($orderId);
    }

    /**
     * @param string $orderId
     *
     * @return void
     */
    public function notifyOrderById(string $orderId): void
    {
        $this->orderManagement->notify($orderId);
    }

    /**
     * @param Order $order
     *
     * @return void
     */
    public function saveOrderInRepository(Order $order): void
    {
        $this->orderRepository->save($order);
    }


}
