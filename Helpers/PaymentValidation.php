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

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Entities\Instalment;
use Alma\API\Entities\Payment;
use Alma\API\RequestError;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Phrase;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Processor as PaymentProcessor;


class AlmaPaymentValidationError extends \Exception {
    private $returnPath;

    public function __construct($message = "", $returnPath="checkout/onepage/failure", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->returnPath = $returnPath;
    }

    public function getReturnPath()
    {
        return $this->returnPath;
    }
}


class PaymentValidation
{
    private $checkoutSession;
    private $searchCriteriaBuilder;
    private $alma;
    private $paymentProcessor;
    private $orderRepository;
    private $orderSender;
    private $orderManagement;
    private $quoteRepository;

    public function __construct(
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
     * @param $paymentId
     * @return mixed
     * @throws AlmaPaymentValidationError
     */
    public function validatePayment($paymentId)
    {
        $errorMessage = __('There was an error when validating your payment. Please try again or contact us if the problem persists.')->render();

        try {

            try {
                $almaPayment = $this->alma->payments->fetch($paymentId);
            } catch (RequestError $e) {
                $internalError = __(
                    "Error fetching payment information from Alma for payment %s: %s",
                    $paymentId,
                    $e->getMessage()
                );

                $this->logger->error($internalError->render());
                throw new AlmaPaymentValidationError($errorMessage);
            }

            // The stored Order ID is an increment ID, so we need to get the order with a search in all orders
            $orderId = $almaPayment->custom_data['order_id'];
            $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $orderId, 'eq')->create();

            /** @var Order $order */
            $order = $this->orderRepository->getList($searchCriteria)->getFirstItem();
            if (!$order) {
                $this->logger->error("Error: cannot get order {$orderId} details back");
                throw new AlmaPaymentValidationError($errorMessage);
            }

            $payment = $order->getPayment();
            if (!$payment) {
                $internalError = __("Cannot get payment information from order %s", $order->getIncrementId());

                $this->logger->error($internalError->render());
                $this->addCommentToOrder($order, $internalError);

                throw new AlmaPaymentValidationError($errorMessage);
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

                throw new AlmaPaymentValidationError($errorMessage);
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

                throw new AlmaPaymentValidationError($errorMessage);
            }

            if (in_array($order->getState(), [Order::STATE_NEW, Order::STATE_PENDING_PAYMENT])) {
                $order->setCanSendNewEmailFlag(true);
                $order->setState(Order::STATE_PROCESSING);
                $order->setStatus(Order::STATE_PROCESSING);
                $this->orderRepository->save($order);

                // Register successful capture to update order state and generate invoice
                $this->paymentProcessor->registerCaptureNotification($payment, $payment->getBaseAmountAuthorized());
                $this->orderManagement->notify($order->getId());

                $this->addCommentToOrder($order, __('First instalment captured successfully'), Order::STATE_PROCESSING);
                $this->orderRepository->save($order);

                $quote = $this->quoteRepository->get($order->getQuoteId());
                $quote->setIsActive(false);
                $this->quoteRepository->save($quote);

                return 'checkout/onepage/success';

            } elseif ($order->getState() == Order::STATE_CANCELED) {
                throw new AlmaPaymentValidationError(__('Your order has been canceled'), 'checkout/onepage/failure/');

            } elseif (in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE, Order::STATE_HOLDED, Order::STATE_PAYMENT_REVIEW])) {
                $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                $this->orderRepository->save($order);

                return 'checkout/onepage/success';
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            throw new AlmaPaymentValidationError($errorMessage);
        }

        throw new AlmaPaymentValidationError($errorMessage, 'checkout/cart');
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
