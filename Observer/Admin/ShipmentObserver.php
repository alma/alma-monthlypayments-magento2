<?php

namespace Alma\MonthlyPayments\Observer\Admin;

use Alma\API\Entities\Payment;
use Alma\API\RequestError;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use \InvalidArgumentException;

class ShipmentObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var AlmaClient
     */
    private $almaClient;

    public function __construct(Logger $logger, AlmaClient $almaClient)
    {
        $this->logger = $logger;
        $this->almaClient = $almaClient;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $order = $this->getOrder($observer);
        if ($this->isAlmaOrder($order) && $this->orderIsTrigger($order)) {
            try {
                $almaPaymentId = $this->getPaymentIdFromUrl($order);
                $almaPayment = $this->getAlmaPaymentById($almaPaymentId);

                if ($this->paymentHasDeferredTrigger($almaPayment) && !$this->isAlreadyTriggered($almaPayment)) {
                    $this->almaClient->getDefaultClient()->payments->trigger($almaPaymentId);
                    $this->logger->info('Alma payment trigger is sent for payment ', [$almaPaymentId]);
                    $order->addCommentToStatusHistory(__('Alma payment trigger is sent'));
                }
            } catch (RequestError | AlmaClientException | InvalidArgumentException $e) {
                $this->logger->error('Error in payment on shipping : ', [$e->getMessage()]);
                return;
            }
        }
    }

    /**
     * @param $almaPayment
     * @return bool
     */
    private function paymentHasDeferredTrigger($almaPayment): bool
    {
        return $almaPayment->deferred_trigger;
    }

    /**
     * @param $almaPayment
     * @return bool
     */
    private function isAlreadyTriggered($almaPayment): bool
    {
        return isset($almaPayment->deferred_trigger_applied);
    }

    /**
     * @param $almaPaymentId
     *
     * @return Payment
     * @throws AlmaClientException
     * @throws RequestError
     */
    private function getAlmaPaymentById($almaPaymentId): Payment
    {
        return $this->almaClient->getDefaultClient()->payments->fetch($almaPaymentId);
    }

    /**
     * @param $observer
     * @return Order
     */
    private function getOrder($observer): Order
    {
        return $observer->getEvent()->getShipment()->getOrder();
    }

    /**
     * @param Order $order
     * @return string
     */
    private function getPaymentMethodName(Order $order): string
    {
        return $this->getOrderPayment($order)->getMethod();
    }

    /**
     * @param Order $order
     * @return OrderPaymentInterface|null
     */
    private function getOrderPayment(Order $order): ?OrderPaymentInterface
    {
        return $order->getPayment();
    }

    /**
     * @param $order
     * @return bool
     */
    private function isAlmaOrder($order): bool
    {
        return ($this->getPaymentMethodName($order) === Config::CODE);
    }

    /**
     * @param $order
     * @return bool
     */
    private function orderIsTrigger($order): bool
    {
        $isTrigger = false;
        if ($this->getOrderPayment($order)->getAdditionalInformation(Config::ORDER_PAYMENT_TRIGGER)) {
            $isTrigger = true;
        }
        return $isTrigger;
    }

    /**
     * @param $order
     * @return string
     * @throws InvalidArgumentException
     */
    private function getPaymentIdFromUrl($order): string
    {
        $pattern = "%^(https:\/\/pay.sandbox.getalma.eu\/|https:\/\/pay.getalma.eu\/)(\w{34})$%";
        if ($this->getOrderPayment($order)->getAdditionalInformation(Config::ORDER_PAYMENT_URL)) {
            $paymentId = $this->getOrderPayment($order)->getAdditionalInformation(Config::ORDER_PAYMENT_URL);
            if (preg_match($pattern, $paymentId, $matches)) {
                return $matches[2];
            }
        }
        throw new InvalidArgumentException('No payment_id in order');
    }
}
