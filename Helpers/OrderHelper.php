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
     * @param OrderInterface $order
     *
     * @return string
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
        OrderManagementInterface $orderManagement,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->orderManagement = $orderManagement;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * Load a specified order.
     * @param string $orderId
     *
     * @return OrderInterface
     */
    public function getOrder(string $orderId): OrderInterface
    {
        $orderModel = $this->orderFactory->create();
        return $orderModel->loadByIncrementId($orderId);
    }

    /**
     * Cancels a specified order.
     * @param string $orderId
     *
     * @return void
     */
    public function cancel(string $orderId): void
    {
        $this->orderManagement->cancel($orderId);
    }

    /**
     * Emails a user a specified order.
     * @param string $orderId
     *
     * @return void
     */
    public function notify(string $orderId): void
    {
        $this->orderManagement->notify($orderId);
    }

    /**
     * Performs persist operations for a specified order.
     * @param Order $order
     *
     * @return void
     */
    public function save(Order $order): void
    {
        $this->orderRepository->save($order);
    }

    /**
     * Get an order collection
     */
    public function getOrderCollectionByCustomerId(int $customerId)
    {
        $this->logger->info('Get Order collection', []);

        $orderCollection = $this->orderFactory->create($customerId)
        ->addFieldToSelect('*')
        ->addFieldToFilter('status', ['in' => [Order::STATE_COMPLETE, Order::STATE_PROCESSING]])
        ->setOrder(
            'created_at',
            'desc'
        )
        ->setPageSize(10)
        ->setCurPage(1);
         $this->logger->info('Order collection', [$orderCollection]);
         return $orderCollection;
    }
}
