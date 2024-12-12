<?php
/**
 * 2018 Alma / Nabla SAS
 *
 * THE MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
 * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @author    Alma / Nabla SAS <contact@getalma.eu>
 * @copyright 2018 Alma / Nabla SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 *
 */

namespace Alma\MonthlyPayments\Controller\Payment;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\OrderHelper;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Alma\MonthlyPayments\Helpers\QuoteHelper;
use Alma\MonthlyPayments\Model\Exceptions\InPagePayException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;

class Pay extends Action
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var QuoteHelper
     */
    private $quoteHelper;
    /**
     * @var PaymentPlansHelper
     */
    private $paymentPlansHelper;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var OrderHelper
     */
    private $orderHelper;
    /**
     * @var AlmaClient
     */
    private $almaClient;

    /**
     * Pay constructor.
     * @param Logger $logger
     * @param Http $request
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param QuoteHelper $quoteHelper
     * @param PaymentPlansHelper $paymentPlansHelper
     * @param OrderHelper $orderHelper
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Logger $logger,
        Http $request,
        Context $context,
        CheckoutSession $checkoutSession,
        QuoteHelper $quoteHelper,
        PaymentPlansHelper $paymentPlansHelper,
        OrderHelper $orderHelper,
        JsonFactory $jsonFactory,
        AlmaClient $almaClient

    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->quoteHelper = $quoteHelper;
        $this->paymentPlansHelper = $paymentPlansHelper;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        $this->request = $request;
        $this->orderHelper = $orderHelper;
        $this->almaClient = $almaClient;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order) {
            $this->logger->error('Error: cannot find order in session');

            throw new LocalizedException(__('Error: cannot find order in session'));
        }

        $payment = $order->getPayment();
        if (!$payment) {

            return $this->errorProcess($order, 'Error: getting payment information from session');
        }

        $paymentID = $payment->getAdditionalInformation()[Config::ORDER_PAYMENT_ID];
        if (empty($paymentID)) {

            return $this->errorProcess($order, 'Error: no payment id found in session');
        }

        $url = $payment->getAdditionalInformation()[Config::ORDER_PAYMENT_URL];
        if (empty($url)) {

            return $this->errorProcess($order, 'Error: no payment URL found in session', $paymentID);
        }

        $paymentPlanKey = $payment->getAdditionalInformation()[Config::ORDER_PAYMENT_PLAN_KEY];
        if (empty($paymentPlanKey)) {

            return $this->errorProcess($order, 'Error: no payment payment plan key found in session', $paymentID);
        }

        if ($this->request->isPost()) {
            try {
                $postPaymentPlanKey = $this->getRequestPaymentPlanKey();
            } catch (InPagePayException $e) {

                return $this->errorProcess($order, $e->getMessage(), $paymentID);
            }
            if ($paymentPlanKey != $postPaymentPlanKey) {

                return $this->errorProcess($order, 'Error: posted payment plan key and order payment plan key are not the same', $paymentID);
            }
        }

        if ($this->paymentPlansHelper->isInPageAllowed()) {
            $response = $this->jsonFactory->create();
            $response->setData(['error' => false, 'paymentId' => $paymentID]);

            return $response;
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($url);

        return $redirect;

    }

    /**
     * Get request content.
     *
     * @return string
     * @throws InPagePayException
     */
    private function getRequestPaymentPlanKey(): string
    {
        $requestContent = json_decode($this->request->getContent(), true);

        if (
            !isset($requestContent)
            || !isset($requestContent['planKey'])
            || !preg_match('!general:([\d]+):([\d]+):([\d]+)!', $requestContent['planKey'])
        ) {

            throw new InPagePayException('Request data are not valid', $this->logger);
        }

        return $requestContent['planKey'];
    }

    /**
     * Error process : restore cart and throw an exception
     *
     * @param Order $order
     * @param string $message
     * @param string $paymentID
     * @return Redirect | Json
     * @throws NoSuchEntityException
     */
    private function errorProcess(Order $order, string $message, string $paymentID = '')
    {
        $this->logger->error('Error in pay process :', [$message]);
        $this->quoteHelper->restoreQuote($order);
        if ($order->canCancel()) {
            $order->cancel();
            $order->addStatusToHistory(Order::STATE_CANCELED, $message);
            $this->orderHelper->save($order);
        }
        if ($this->request->isPost()) {
            $response = $this->jsonFactory->create();
            $response->setStatusHeader(400);
            $response->setData(['error' => true, 'message' => $message]);
            $this->messageManager->addWarningMessage(__($message));

            return $response;
        }
        if ($paymentID !== '') {
            try {
                $this->almaClient->getDefaultClient()->payments->cancel($paymentID);
            } catch (\Exception $e) {
                $this->logger->error('Error in cancel Alma payment', [$e->getMessage()]);
            }
        }
        $redirect = $this->resultRedirectFactory->create();
        $this->messageManager->addWarningMessage(__('Something went wrong while.'));

        return $redirect->setPath('checkout/cart/');
    }
}
