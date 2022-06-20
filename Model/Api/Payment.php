<?php
/**
 * 2018-2020 Alma SAS
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
 * @author    Alma SAS <contact@getalma.eu>
 * @copyright 2018-2020 Alma SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace Alma\MonthlyPayments\Model\Api;

use Alma\API\RequestError;
use Alma\MonthlyPayments\Api\Data\PaymentValidationResultInterface;
use Alma\MonthlyPayments\Api\Data\PaymentValidationResultInterfaceFactory;
use Alma\MonthlyPayments\Api\PaymentInterface;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\PaymentValidation;
use Alma\MonthlyPayments\Model\Exceptions\AlmaPaymentValidationException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class Payment implements PaymentInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var PaymentValidation
     */
    private $paymentValidation;
    /**
     * @var PaymentValidationResultInterfaceFactory
     */
    private $validationResultFactory;

    /**
     * Payment constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentValidation $paymentValidation
     * @param PaymentValidationResultInterfaceFactory $validationResultFactory
     */
    public function __construct(OrderRepositoryInterface $orderRepository, PaymentValidation $paymentValidation, PaymentValidationResultInterfaceFactory $validationResultFactory)
    {
        $this->orderRepository = $orderRepository;
        $this->paymentValidation = $paymentValidation;
        $this->validationResultFactory = $validationResultFactory;
    }

    /**
     * @inheritDoc
     * @return PaymentValidationResultInterface
     * @throws NoSuchEntityException
     * @throws RequestError
     */
    public function validate($paymentId)
    {
        /** @var \Alma\API\Entities\Payment $almaPayment */
        try {
            $almaPayment = $this->paymentValidation->getAlmaPayment($paymentId);
        } catch (RequestError $e) {
            if ($e->response->responseCode == 404) {
                throw new NoSuchEntityException(__("Alma Payment not found"), $e);
            } else {
                throw $e;
            }
        }

        /** @var Order $order */
        $order = $this->paymentValidation->findOrderForPayment($almaPayment);

        if (!$order) {
            throw new NoSuchEntityException(__("Order not found"));
        }

        /** @var PaymentValidationResultInterface $validationResult */
        $validationResult = $this->validationResultFactory->create();

        try {
            $this->paymentValidation->validateOrderPayment($order, $almaPayment, false);
        } catch (AlmaPaymentValidationException $e) {
            $validationResult->setReason($e->getMessage());
            return $validationResult;
        }

        $validationResult->setValid(true);
        $validationResult->setPurchaseAmount($almaPayment->purchase_amount);
        $validationResult->setOrderId($order->getId());
        $validationResult->setOrderRef($order->getIncrementId());
        $validationResult->setOrderDate(strtotime($order->getCreatedAt()));

        return $validationResult;
    }

    /**
     * @inheritDoc
     * @throws NotFoundException
     */
    public function getPaymentUrl($orderId)
    {
        $order = $this->orderRepository->get($orderId);
        if (!$order) {
            throw new NotFoundException(__("Order not found"));
        }

        $payment = $order->getPayment();
        if (!$payment) {
            throw new NotFoundException(__("Payment information for order not found"));
        }

        return $payment->getAdditionalInformation(Config::ORDER_PAYMENT_URL);
    }
}
