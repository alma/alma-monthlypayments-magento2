<?php

namespace Alma\MonthlyPayments\Observer;

use Alma\API\Exceptions\AlmaException;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Exceptions\OrderShipmentException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\OrderRepository;

class ShipmentTrackObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var AlmaClient
     */
    private $almaClient;

    /**
     * @param OrderRepository $orderRepository
     * @param AlmaClient $almaClient
     * @param Logger $logger
     */
    public function __construct(
        OrderRepository $orderRepository,
        AlmaClient      $almaClient,
        Logger          $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->almaClient = $almaClient;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Track $track */
        $track = $observer->getEvent()->getData('track');
        try {
            $order = $this->getOrderInRepository($track);
            $payment = $order->getPayment();
            $almaPaymentId = $this->getAlmaPaymentId($payment);
        } catch (OrderShipmentException $e) {
            $this->logger->info('Shipment Track observer no Alma payment', [$e->getMessage()]);
            return;
        }

        try {
            $almaClient = $this->almaClient->getDefaultClient();
            $almaPayment = $almaClient->payments->fetch($almaPaymentId);

            $orderExternalId = null;
            foreach ($almaPayment->orders as $almaOrder) {
                if ($order->getIncrementId() === $almaOrder->getMerchantReference()) {
                    $orderExternalId = $almaOrder->getExternalId();
                }
            }

            if (!isset($orderExternalId)) {
                $almaOrder = $almaClient->payments->addOrder(
                    $almaPaymentId,
                    [
                        'merchant_reference' => $order->getIncrementId()
                    ]
                );
                $orderExternalId = $almaOrder->getExternalId();
            }
            $almaClient->orders->addTracking($orderExternalId, $track->getCarrierCode(), $track->getTrackNumber());
        } catch (AlmaException $e) {
            $this->logger->error('Shipment Track observer Alma client error', [$e]);
            return;
        }
    }

    /**
     * @param OrderPaymentInterface|null $payment
     * @return string
     * @throws OrderShipmentException
     */
    private function getAlmaPaymentId(?OrderPaymentInterface $payment): string
    {
        if (
            $payment === null ||
            $payment->getMethod() !== Config::CODE
        ) {
            throw new OrderShipmentException('Shipment Track observer no Alma payment');
        }
        $almaPaymentId = $payment->getAdditionalInformation()[Config::ORDER_PAYMENT_ID] ?? null;
        if (!$almaPaymentId) {
            throw new OrderShipmentException('Shipment Track observer no Alma payment ID in order');
        }
        return $almaPaymentId;
    }

    /**
     * @param Track $track
     * @return OrderInterface
     * @throws OrderShipmentException
     */
    private function getOrderInRepository(Track $track): OrderInterface
    {
        try {
            return $this->orderRepository->get($track->getOrderId());
        } catch (InputException|NoSuchEntityException $e) {
            throw new OrderShipmentException('Shipment Track observer getOrderInRepository No Order');
        }
    }
}
