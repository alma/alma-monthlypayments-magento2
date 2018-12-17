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
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Processor as PaymentProcessor;

class ReturnAction extends Action
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

    public function __construct(
        Context $context,
        Logger $logger,
        CheckoutSession $checkoutSession,
        AlmaClient $almaClient,
        PaymentProcessor $paymentProcessor,
        OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->alma = $almaClient->getDefaultClient();
        $this->paymentProcessor = $paymentProcessor;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect|void
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();

        $errorMessage = __('There was an error when validating your payment. Please try again or contact us if the problem persists.');

        try {
            $order = $this->checkoutSession->getLastRealOrder();
            if (!$order) {
                $this->logger->error("Error: cannot get last order's details back");
                throw new LocalizedException($errorMessage);
            }

            $payment = $order->getPayment();
            if (!$payment) {
                $internalError = __("Cannot get payment information from order %s", $order->getIncrementId());
                $this->logger->error($internalError->render());
                $order->addCommentToStatusHistory($internalError);

                throw new LocalizedException($errorMessage);
            }

            try {
                $almaPayment = $this->alma->payments->fetch($this->getRequest()->getParam('pid'));
            } catch (RequestError $e) {
                $internalError = __(
                    "Error fetching payment information from Alma for order %s : %s",
                    $order->getIncrementId(),
                    $e->getMessage()
                );
                $this->logger->error($internalError->render());
                $order->addCommentToStatusHistory($internalError);

                throw new LocalizedException($errorMessage);
            }

            if (Functions::priceToCents($payment->getAmountAuthorized()) !== $almaPayment->purchase_amount) {
                $internalError = __(
                    "Paid amount (%1) does not match due amount (%2) for order %3",
                    Functions::priceFromCents($almaPayment->purchase_amount),
                    $payment->getAmountAuthorized(),
                    $order->getIncrementId()
                );
                $this->logger->error($internalError->render());
                $order->addCommentToStatusHistory($internalError);

                throw new LocalizedException($errorMessage);
            }

            $first_instalment = $almaPayment->payment_plan[0];
            if ( $almaPayment->state !== Payment::STATE_IN_PROGRESS || $first_instalment->state !== Instalment::STATE_PAID ) {
                $internalError = __(
                    "Payment state incorrect (%s & %s) for order %s",
                    $almaPayment->state,
                    $first_instalment->state,
                    $order->getIncrementId()
                );
                $this->logger->error($internalError->render());
                $order->addCommentToStatusHistory($internalError);

                throw new LocalizedException($errorMessage);
            }

            // Register successful capture to update order state and generate invoice
            $order->addCommentToStatusHistory(__('First instalment captured successfully'));
            $order->setCanSendNewEmailFlag(true);
            $order->setSendEmail(true);

            $this->paymentProcessor->registerCaptureNotification($payment, $payment->getBaseAmountAuthorized());

            // notify customer
            /** @var Invoice $invoice */
            $invoice = $payment->getCreatedInvoice();
            if ($invoice && !$order->getEmailSent()) {
                $this->orderSender->send($order);

                $order->addCommentToStatusHistory(
                    __('You notified customer about invoice %1', $invoice->getIncrementId())
                )->setIsCustomerNotified(true);
            }

            $this->orderRepository->save($order);

            $this->_redirect('checkout/onepage/success');
            return;
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addExceptionMessage($e, $errorMessage);
        }

        return $redirect->setPath('checkout/cart');
    }
}
