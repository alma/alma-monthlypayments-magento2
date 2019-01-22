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

use Alma\API\Entities\Instalment;
use Alma\API\Entities\Payment;
use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Functions;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Phrase;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Processor as PaymentProcessor;

class Ipn extends Action
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var \Alma\API\Client
     */
    private $alma;
    /**
     * @var PaymentProcessor
     */
    private $paymentProcessor;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var OrderSender
     */
    private $orderSender;
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        Context $context,
        Logger $logger,
        CheckoutSession $checkoutSession,
        AlmaClient $almaClient,
        PaymentProcessor $paymentProcessor,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        QuoteRepository $quoteRepository,
        OrderSender $orderSender,
        OrderManagementInterface $orderManagement
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->alma = $almaClient->getDefaultClient();
        $this->paymentProcessor = $paymentProcessor;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
        $this->orderManagement = $orderManagement;
        $this->quoteRepository = $quoteRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var Json $json */
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $paymentId = $this->getRequest()->getParam('pid');

            try {
                $almaPayment = $this->alma->payments->fetch($paymentId);
            } catch (RequestError $e) {
                $internalError = __(
                    "Error fetching payment information from Alma for payment %s: %s",
                    $paymentId,
                    $e->getMessage()
                );

                $this->logger->error($internalError->render());

                return $json->setData(["error" => "Cannot fetch Alma payment"])->setHttpResponseCode(500);
            }

            // The stored Order ID is an increment ID, so we need to get the order with a search in all orders
            $orderId = $almaPayment->custom_data['order_id'];
            $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $orderId, 'eq')->create();

            /** @var Order $order */
            $order = $this->orderRepository->getList($searchCriteria)->getFirstItem();
            if (!$order) {
                $this->logger->error("Error: cannot get order {$orderId} details back");
                return $json->setData(["retry" => true, "error" => "Order not found"])->setHttpResponseCode(500);
            }

            $payment = $order->getPayment();
            if (!$payment) {
                $internalError = __("Cannot get payment information from order %s", $order->getIncrementId());

                $this->logger->error($internalError->render());
                $this->addCommentToOrder($order, $internalError);

                return $json->setData(["retry" => true, "error" => "Payment info not found"])->setHttpResponseCode(500);
            }

            // Check that there's no price mismatch between the order amount and what's been paid
            if (Functions::priceToCents($order->getGrandTotal()) !== $almaPayment->purchase_amount) {
                $internalError = __(
                    "Paid amount (%1) does not match due amount (%2) for order %3",
                    Functions::priceFromCents($almaPayment->purchase_amount),
                    $payment->getAmountAuthorized(),
                    $order->getIncrementId()
                );

                $this->logger->error($internalError->render());
                $this->addCommentToOrder($order, $internalError, Order::STATUS_FRAUD);
                $this->orderManagement->cancel($order->getId());

                try {
                    $this->alma->payments->flagAsPotentialFraud($paymentId, Payment::FRAUD_AMOUNT_MISMATCH);
                } catch (\Exception $e) {
                    $this->logger->info("Error flagging payment {$paymentId} as fraudulent");
                }

                return $json->setData(["error" => Payment::FRAUD_AMOUNT_MISMATCH])->setHttpResponseCode(500);
            }

            // Check that the Alma API has correctly registered the first installment as paid
            $firstInstalment = $almaPayment->payment_plan[0];
            if (!in_array($almaPayment->state, [Payment::STATE_IN_PROGRESS, Payment::STATE_PAID]) || $firstInstalment->state !== Instalment::STATE_PAID) {
                $internalError = __(
                    "Payment state incorrect (%1 & %2) for order %3",
                    $almaPayment->state,
                    $firstInstalment->state,
                    $order->getIncrementId()
                );

                $this->logger->error($internalError->render());
                $this->addCommentToOrder($order, $internalError, Order::STATUS_FRAUD);
                $this->orderManagement->cancel($order->getId());

                try {
                    $this->alma->payments->flagAsPotentialFraud($paymentId, Payment::FRAUD_STATE_ERROR . ": " . $internalError->render());
                } catch (\Exception $e) {
                    $this->logger->info("Error flagging payment {$paymentId} as fraudulent");
                }

                return $json->setData(["error" => Payment::FRAUD_STATE_ERROR])->setHttpResponseCode(500);
            }

            if (in_array($order->getState(), [Order::STATE_NEW, Order::STATE_PENDING_PAYMENT])) {
                // Register successful capture to update order state and generate invoice
                $this->addCommentToOrder($order, __('First instalment captured successfully'));
                $order->setCanSendNewEmailFlag(true);

                $this->paymentProcessor->registerCaptureNotification($payment, $payment->getBaseAmountAuthorized());
                $this->orderRepository->save($order);

                $this->orderManagement->notify($order->getId());

                $this->orderRepository->save($order);

                $quote = $this->quoteRepository->get($order->getQuoteId());
                $quote->setIsActive(false);
                $this->quoteRepository->save($quote);

                return $json->setData([])->setHttpResponseCode(200);

            } elseif ($order->getState() == Order::STATE_CANCELED) {
                return $json->setData(["error" => "Order was canceled"])->setHttpResponseCode(500);
            } elseif (in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE, Order::STATE_HOLDED, Order::STATE_PAYMENT_REVIEW])) {
                return $json->setData(["info" => "Order already validated"])->setHttpResponseCode(200);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return $json->setData(["error" => $e->getMessage()])->setHttpResponseCode(500);
        }

        return $json->setData(["error" => "Order in unexpected state {$order->getState()}"])->setHttpResponseCode(500);
    }

    /**
     * @param Order $order
     * @param string|Phrase $comment
     * @param bool $status
     * @return \Magento\Sales\Api\Data\OrderStatusHistoryInterface
     */
    private function addCommentToOrder($order, $comment, $status=false) {
        if (method_exists($order, 'addCommentToStatusHistory') && is_callable([$order, 'addCommentToStatusHistory'])) {
            $statusHistoryItem = $order->addCommentToStatusHistory($comment, $status);
        } else {
            $statusHistoryItem = $order->addStatusHistoryComment($comment, $status);
        }

        return $statusHistoryItem;
    }
}
