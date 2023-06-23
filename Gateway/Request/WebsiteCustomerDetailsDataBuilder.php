<?php
/**
 * 2018-2019 Alma SAS
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
 * @copyright 2018-2019 Alma SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace Alma\MonthlyPayments\Gateway\Request;

use Alma\MonthlyPayments\Helpers\Functions;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\OrderHelper;
use Magento\Payment\Gateway\Data\Order\OrderAdapter;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderInterface;

class WebsiteCustomerDetailsDataBuilder implements BuilderInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var OrderHelper
     */
    private $orderHelper;

    public function __construct(
        Logger $logger,
        OrderHelper $orderHelper
    ) {
        $this->logger = $logger;
        $this->orderHelper = $orderHelper;
    }

    /**
     * Build website_customer_details data
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var OrderAdapter $order */
        $order = $paymentDO->getOrder();

        $previousOrders = [];
        $isGuest = true;

        if ($order->getCustomerId()) {
            $isGuest = false;
            $customerOrderCollection = $this->orderHelper->getValidOrderCollectionByCustomerId($order->getCustomerId());

            foreach ($customerOrderCollection as $previousOrder) {
                $previousOrders[] = $this->formatPreviousOrderForPaymentPayload($previousOrder);
            }
        }
        return [
            'website_customer_details' => [
                'is_guest' => $isGuest,
                'previous_orders' => $previousOrders
            ]
        ];
    }

    /**
     * Format Previous orders data
     *
     * @param OrderInterface $order
     * @return array
     */
    private function formatPreviousOrderForPaymentPayload(OrderInterface $order): array
    {
        return [
            "purchase_amount"=> Functions::priceToCents($order->getGrandTotal()),
            "payment_method"=> $this->orderHelper->getOrderPaymentMethodName($order),
            "shipping_method"=> $this->orderHelper->getOrderShippingMethodName($order),
            "created"=> strtotime($order->getCreatedAt()),
            "items" => $this->orderHelper->formatOrderItems($order->getItems())
        ];
    }
}
