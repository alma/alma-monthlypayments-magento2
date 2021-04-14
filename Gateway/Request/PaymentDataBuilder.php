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

namespace Alma\MonthlyPayments\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\Functions;
use Alma\MonthlyPayments\Model\Data\Address;
use Alma\MonthlyPayments\Observer\PaymentDataAssignObserver;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class PaymentDataBuilder implements BuilderInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var Config
     */
    private $config;

    public function __construct(UrlInterface $urlBuilder, CheckoutSession $checkoutSession, Config $config)
    {
        $this->urlBuilder = $urlBuilder;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        $order = $paymentDO->getOrder();
        $orderId = $order->getOrderIncrementId();
        $quoteId = $this->checkoutSession->getQuoteId();

        $planKey = $payment->getAdditionalInformation(PaymentDataAssignObserver::SELECTED_PLAN);
        $planConfig = $this->config->getPaymentPlansConfig()->getPlans()[$planKey];

        return [
            'payment' => array_merge(
                $planConfig->getPaymentData(),
                [
                    'return_url' => $this->config->getReturnUrl(),
                    'ipn_callback_url' => $this->config->getIpnCallbackUrl(),
                    'customer_cancel_url' => $this->config->getCustomerCancelUrl(),
                    'purchase_amount' => Functions::priceToCents((float)$order->getGrandTotalAmount()),
                    'shipping_address' => Address::dataFromAddress($order->getShippingAddress()),
                    'billing_address' => Address::dataFromAddress($order->getBillingAddress()),
                    'custom_data' => [
                        'order_id' => $orderId,
                        'quote_id' => $quoteId
                    ]
                ]
            ),
        ];
    }
}
