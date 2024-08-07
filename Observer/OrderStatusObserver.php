<?php

namespace Alma\MonthlyPayments\Observer;

use Alma\API\Client;
use Alma\API\Entities\Order as AlmaOrder;
use Alma\API\Entities\Payment;
use Alma\API\Exceptions\AlmaException;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Exceptions\OrderStatusException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class OrderStatusObserver implements ObserverInterface
{
    private $logger;
    private $almaClient;

    public function __construct(
        Logger     $logger,
        AlmaClient $almaClient
    ) {
        $this->logger = $logger;
        $this->almaClient = $almaClient;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');
        $payment = $order->getPayment();

        if (
            $order->getState() === Order::STATE_NEW
            || !$payment
            || $payment->getMethod() !== Config::CODE
            || !array_key_exists(Config::ORDER_PAYMENT_ID, $payment->getAdditionalInformation())
        ) {
            return;
        }

        $almaPaymentId = $payment->getAdditionalInformation()[Config::ORDER_PAYMENT_ID];

        try {
            $almaClient = $this->getAlmaClient();
            $almaPayment = $this->getAlmaPayment($almaClient, $almaPaymentId);
            $almaOrder = $this->getAlmaOrder($almaPayment, $order->getIncrementId());
        } catch (OrderStatusException $e) {
            $this->logger->error('OrderStatus Exception :', [$e->getMessage()]);
            return;
        }

        try {
            $almaClient->orders->sendStatus($almaOrder->id, ['status' => $order->getStatus() ?? '', 'is_shipped' => $order->hasShipments()]);
        } catch (AlmaException $e) {
            $this->logger->error('Impossible to send order Status', [$e->getMessage()]);
        }
    }

    /**
     * @return Client
     * @throws OrderStatusException
     */
    private function getAlmaClient(): Client
    {
        try {
            return $this->almaClient->getDefaultClient();
        } catch (AlmaClientException $e) {
            throw new OrderStatusException('Impossible to initialize Alma', $this->logger, 0, $e);
        }
    }

    /**
     * @param Client $almaClient
     * @param string $almaPaymentId
     * @return Payment
     * @throws OrderStatusException
     */
    private function getAlmaPayment(Client $almaClient, string $almaPaymentId): Payment
    {
        try {
            return $almaClient->payments->fetch($almaPaymentId);
        } catch (AlmaException $e) {
            throw new OrderStatusException('Impossible to fetch Payment', $this->logger, 0, $e);
        }
    }


    /**
     * @param Payment $almaPayment
     * @param string $incrementId
     * @return AlmaOrder
     * @throws OrderStatusException
     */
    private function getAlmaOrder(Payment $almaPayment, string $incrementId): AlmaOrder
    {
        if (empty($almaPayment->orders)) {
            throw new OrderStatusException(sprintf('No Orders in Alma payment %s', $almaPayment->id), $this->logger);
        }
        foreach ($almaPayment->orders as $order) {
            if ($order->merchant_reference === $incrementId) {
                return $order;
            }
        }
        throw new OrderStatusException(sprintf('No Order with merchant reference %s in Alma payment %s', $incrementId, $almaPayment->id), $this->logger);
    }

}
