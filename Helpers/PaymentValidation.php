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
use Alma\API\Entities\Payment as AlmaPayment;
use Alma\API\RequestError;
use Alma\MonthlyPayments\Model\Exceptions\AlmaPaymentValidationException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\Processor as PaymentProcessor;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

class PaymentValidation
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var AlmaClient
     */
    private $alma;
    /**
     * @var PaymentProcessor
     */
    private $paymentProcessor;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var BuilderInterface
     */
    private $transactionBuilder;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * PaymentValidation constructor.
     *
     * @param Logger $logger
     * @param CheckoutSession $checkoutSession
     * @param AlmaClient $almaClient
     * @param PaymentProcessor $paymentProcessor
     * @param QuoteRepository $quoteRepository
     * @param BuilderInterface $transactionBuilder
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        Logger $logger,
        CheckoutSession $checkoutSession,
        AlmaClient $almaClient,
        PaymentProcessor $paymentProcessor,
        QuoteRepository $quoteRepository,
        BuilderInterface $transactionBuilder,
        OrderHelper $orderHelper
    ) {
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->alma = $almaClient->getDefaultClient();
        $this->paymentProcessor = $paymentProcessor;
        $this->quoteRepository = $quoteRepository;
        $this->transactionBuilder = $transactionBuilder;
        $this->orderHelper = $orderHelper;
    }

    /**
     * @param string $paymentId ID of Alma payment to fetch
     *
     * @return AlmaPayment
     * @throws AlmaPaymentValidationException
     */
    public function getAlmaPayment(string $paymentId): AlmaPayment
    {
        try {
            $almaPayment = $this->alma->payments->fetch($paymentId);
        } catch (RequestError $e) {
            $requestError = __(
                "Error fetching payment information from Alma for payment %s: %s",
                $paymentId,
                $e->getMessage()
            );
            $this->logger->error($requestError->render());
            throw new AlmaPaymentValidationException($requestError->render());
        }
        return $almaPayment;
    }

    /**
     * @param AlmaPayment $almaPayment
     *
     * @return Order
     * @throws AlmaPaymentValidationException
     */
    public function findOrderForPayment(AlmaPayment $almaPayment): Order
    {
        // The stored Order ID is an increment ID, so we need to get the order with a search in all orders
        $errorMessage = __('Error: cannot get order details back for payment %s', $almaPayment['id'])->render();
        $orderId = $almaPayment->custom_data['order_id'];
        $order = $this->orderHelper->getOrder($orderId);
        if (!$order) {
            $this->logger->error($errorMessage);
            throw new AlmaPaymentValidationException($errorMessage);
        }
        return $order;
    }


    /**
     * @param string $paymentId
     *
     * @return bool `true` if payment is valid
     * @throws AlmaPaymentValidationException
     */
    public function completeOrderIfValid(string $paymentId): bool
    {
        $errorMessage = __('There was an error when validating your payment. Please try again or contact us if the problem persists.')->render();

        try {
            /** @var Order $order */
            $order = null;
            /** @var AlmaPayment $almaPayment */
            $almaPayment = null;
            $almaPayment = $this->getAlmaPayment($paymentId);
            $order = $this->findOrderForPayment($almaPayment);
            return $this->validateOrderPayment($order, $almaPayment, true);
        } catch (AlmaPaymentValidationException $e) {
            $this->logger->critical($e->getMessage());
            throw new AlmaPaymentValidationException($errorMessage);
        }
    }

    /**
     * @param Order $order
     * @param AlmaPayment $almaPayment
     * @param bool $transitionOrder
     *
     * @return bool
     * @throws AlmaPaymentValidationException
     */
    public function validateOrderPayment(Order $order, AlmaPayment $almaPayment, bool $transitionOrder): bool
    {
        $errorMessage = __('There was an error when validating your payment. Please try again or contact us if the problem persists.')->render();

        $payment = $order->getPayment();
        if (!$payment) {
            $internalError = __("Cannot get payment information from order %s", $order->getIncrementId());

            $this->logger->error($internalError->render());
            $this->addCommentToOrder($order, $internalError);
            throw new AlmaPaymentValidationException($errorMessage);
        }

        // Check that there's no price mismatch between the order amount and what's been paid
        if (Functions::priceToCents($order->getGrandTotal()) !== $almaPayment->purchase_amount) {
            $internalError = __(
                "Paid amount (%1) does not match due amount (%2) for order %3",
                Functions::priceFromCents($almaPayment->purchase_amount),
                $payment->getAmountAuthorized(),
                $order->getIncrementId()
            );

            $this->cancelOrder($internalError, $transitionOrder, $order);

            $this->flagAsPotentialFraud($almaPayment, AlmaPayment::FRAUD_AMOUNT_MISMATCH);
            throw new AlmaPaymentValidationException($errorMessage);
        }

        // Check that the Alma API has correctly registered the first installment as paid
        $firstInstalment = $almaPayment->payment_plan[0];
        if (!in_array($almaPayment->state, [AlmaPayment::STATE_IN_PROGRESS, AlmaPayment::STATE_PAID]) || $firstInstalment->state !== Instalment::STATE_PAID) {
            $internalError = __(
                "Payment state incorrect (%1 & %2) for order %3",
                $almaPayment->state,
                $firstInstalment->state,
                $order->getIncrementId()
            );

            $this->cancelOrder($internalError, $transitionOrder, $order);
            $this->flagAsPotentialFraud($almaPayment, AlmaPayment::FRAUD_STATE_ERROR . ": " . $internalError->render());
            throw new AlmaPaymentValidationException($errorMessage);
        }

        if (in_array($order->getState(), [Order::STATE_NEW, Order::STATE_PENDING_PAYMENT])) {
            if ($transitionOrder) {
                $this->processOrder($order, $almaPayment);
            }

            return true;
        } elseif ($order->getState() == Order::STATE_CANCELED) {
            throw new AlmaPaymentValidationException(__('Your order has been canceled'), 'checkout/onepage/failure/');
        } elseif (in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE, Order::STATE_HOLDED, Order::STATE_PAYMENT_REVIEW])) {
            $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
            $this->orderHelper->save($order);
            return true;
        }
        throw new AlmaPaymentValidationException($errorMessage, 'checkout/cart');
    }

    /**
     * @param AlmaPayment $almaPayment
     * @param string $reason
     *
     * @return void
     */
    private function flagAsPotentialFraud(AlmaPayment $almaPayment, string $reason): void
    {
        try {
            $this->alma->payments->flagAsPotentialFraud($almaPayment->id, $reason);
        } catch (RequestError $e) {
            $this->logger->error("Error flagging payment {$almaPayment->id} as fraudulent");
        }
    }

    /**
     * @param Order $order
     * @param AlmaPayment $almaPayment
     *
     * @return void
     * @throws AlmaPaymentValidationException
     */
    public function processOrder(Order $order, AlmaPayment $almaPayment): void
    {
        $order->setCanSendNewEmailFlag(true);
        $order->setState(Order::STATE_PROCESSING);
        $newStatus = $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING);
        $order->setStatus($newStatus);

        // Register successful capture to update order state and generate invoice
        $payment = $order->getPayment();
        $this->addTransactionToPayment($payment, $order, $almaPayment);
        $this->paymentProcessor->registerCaptureNotification($payment, $payment->getBaseAmountAuthorized());
        $this->orderHelper->notify($order->getId());

        // TODO : Paylater / PnX
        $order = $this->addCommentToOrder($order, __('First instalment captured successfully'), $newStatus);

        foreach ($order->getInvoiceCollection() as $invoice) {
            $invoice->setTransactionId($payment->getLastTransId());
        }
        $this->orderHelper->save($order);
        $this->inactiveQuoteById($order->getQuoteId());
    }

    /**
     * @param Order $order
     * @param string $comment
     * @param bool|string $status
     *
     * @return Order
     */
    public function addCommentToOrder(Order $order, string $comment, $status = false): Order
    {
        if (method_exists($order, 'addCommentToStatusHistory') && is_callable([$order, 'addCommentToStatusHistory'])) {
            $order->addCommentToStatusHistory($comment, $status);
        } else {
            $order->addStatusHistoryComment($comment, $status);
        }
        return $order;
    }

    /**
     * @param OrderPayment $payment
     * @param Order $order
     * @param AlmaPayment $almaPayment
     *
     * @return void
     */
    public function addTransactionToPayment(OrderPayment $payment, Order $order, AlmaPayment $almaPayment)
    {
        $paymentData = $this->createPaymentData($order, $almaPayment);
        $transaction = $this->transactionBuilder->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($almaPayment->id)
            ->setAdditionalInformation([Transaction::RAW_DETAILS => $paymentData])
            ->setFailSafe(true)
            ->build(TransactionInterface::TYPE_PAYMENT);

        $payment = $this->addTransactionComment($order, $payment, $transaction);

        $payment->setParentTransactionId(null);
    }

    /**
     * @param Order $order
     * @param AlmaPayment $almaPayment
     *
     * @return array
     */
    public function createPaymentData(Order $order, AlmaPayment $almaPayment): array
    {
        return [
            "total" => $order->getBaseCurrency()->formatTxt($order->getGrandTotal()),
            "created" => $almaPayment->created,
            "deferred_days" => $almaPayment->deferred_days,
            "installments_count" => $almaPayment->installments_count,
            "deferred_months" => $almaPayment->deferred_months,
            "deferred_trigger" => $almaPayment->deferred_trigger ? 'yes' : 'no',
            "deferred_trigger_description" => $almaPayment->deferred_trigger_description
        ];
    }

    /**
     * @param Order $order
     * @param OrderPayment $payment
     * @param TransactionInterface $transaction
     *
     * @return OrderPayment
     */
    public function addTransactionComment(Order $order, OrderPayment $payment, TransactionInterface $transaction): OrderPayment
    {
        $formattedPrice = $order->getBaseCurrency()->formatTxt(
            $order->getGrandTotal()
        );
        $message = __('The authorized amount is %1.', $formattedPrice);
        $payment->addTransactionCommentsToOrder(
            $transaction,
            $message
        );
        return $payment;
    }

    /**
     * @param string $orderId
     *
     * @return void
     * @throws AlmaPaymentValidationException
     */
    public function inactiveQuoteById(string $orderId): void
    {
        try {
            $quote = $this->quoteRepository->get($orderId);
        } catch (NoSuchEntityException $e) {
            throw new AlmaPaymentValidationException($e->getMessage());
        }
        $quote->setIsActive(false);
        $this->quoteRepository->save($quote);
    }

    /**
     * Cancel order and add a comment
     *
     * @param Phrase $internalError
     * @param bool $transitionOrder
     * @param Order $order
     *
     * @return void
     */
    public function cancelOrder(Phrase $internalError, bool $transitionOrder, Order $order): void
    {
        $this->logger->error('internal Error', [$internalError->render()]);
        if ($transitionOrder) {
            $order = $this->addCommentToOrder($order, $internalError->render(), Order::STATUS_FRAUD);
            $this->orderHelper->save($order);
            $this->orderHelper->cancel($order->getId());
        }
    }
}
