<?php

namespace Alma\MonthlyPayments\Controller\Payment;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\OrderHelper;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;

class CancelInPagePayment implements ActionInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var OrderHelper
     */
    private $orderHelper;
    private $almaClient;

    /**
     * @param Logger $logger
     * @param JsonFactory $jsonFactory
     * @param Session $session
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        Logger $logger,
        JsonFactory $jsonFactory,
        Session $session,
        OrderHelper $orderHelper,
        AlmaClient $almaClient
    ) {
        $this->logger = $logger;
        $this->jsonFactory = $jsonFactory;
        $this->session = $session;
        $this->orderHelper = $orderHelper;
        $this->almaClient = $almaClient;
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $response = $this->jsonFactory->create();
        $order = $this->session->getLastRealOrder();

        if (!$order) {
            $this->error($response, 'No Order in session');

            return $response;
        }

        if (!$order->canCancel()) {
            $this->error($response, 'Order can not be cancelled');

            return $response;
        }
        $order->cancel()->addStatusToHistory(Order::STATE_CANCELED, 'In Page Payment canceled by customer');
        $this->session->restoreQuote();
        $this->orderHelper->save($order);

        $paymentID = $order->getPayment()->getAdditionalInformation()[Config::ORDER_PAYMENT_ID];
        try {
            $this->almaClient->getDefaultClient()->payments->cancel($paymentID);
        } catch (\Exception $e) {
            $this->logger->error('Error in cancel Alma payment', [$e->getMessage()]);
        }
        $response->setData(['error' => false, 'payment_id' => $paymentID]);

        return $response;
    }

    /**
     * @param Json $response
     * @param string $message
     * @return Json
     */
    public function error(Json $response, string $message): Json
    {
        $response->setStatusHeader(400);
        $response->setData(['error' => true, 'message' => $message]);

        return $response;
    }
}
