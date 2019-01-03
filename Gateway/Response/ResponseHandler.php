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

namespace Alma\MonthlyPayments\Gateway\Response;

use Alma\API\Entities\Payment;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;

class ResponseHandler implements HandlerInterface
{
    const PAYMENT_URL = 'PAYMENT_URL';

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        /** @var Payment $almaPayment */
        $almaPayment = $response['almaPayment'];

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setTransactionId($almaPayment->id);
        $payment->setAdditionalInformation(self::PAYMENT_URL, $almaPayment->url);
        $payment->setIsTransactionClosed(false);

        $order = $payment->getOrder();
        $payment->setAmountPaid(0);
        $payment->setBaseAmountAuthorized($order->getBaseTotalDue());
        $payment->setAmountAuthorized($order->getTotalDue());

        $order->setCanSendNewEmailFlag(false);

        if (is_callable([$order, 'addCommentToStatusHistory'])) {
            $order->addCommentToStatusHistory(
                __('Successfully created Alma Payment. Redirecting customer & awaiting payment return.'),
                Order::STATE_PENDING_PAYMENT
            );
        } else {
            $order->addStatusHistoryComment(
                __('Successfully created Alma Payment. Redirecting customer & awaiting payment return.'),
                Order::STATE_PENDING_PAYMENT
            );
        }

        $stateObject = SubjectReader::readStateObject($handlingSubject);
        $stateObject->setData('status', Order::STATE_PENDING_PAYMENT);
    }
}
