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
    )
    {
        $this->logger = $logger;
        $this->almaClient = $almaClient;
    }

    /**
     * Execute the observer
     *
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
            $almaClient->payments->addOrderStatusByMerchantOrderReference($almaPaymentId, $order->getIncrementId(), $order->getStatus(), $order->hasShipments());
        } catch (AlmaException|OrderStatusException $e) {
            $this->logger->error('Impossible to send order Status', [$e->getMessage()]);
        }
    }

    /**
     * Get Alma client and handle exception
     *
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


}
