<?php
/**
 * 2018-2023 Alma SAS
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
 * @copyright 2018-2023 Alma SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace Alma\MonthlyPayments\Gateway\Request;

use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\OrderHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * CartDataBuilder build Cart Data for Alma credit Payment Payload
 */
class CartDataBuilder implements BuilderInterface
{
    private $logger;
    private $orderHelper;

    /**
     * @param Logger $logger
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        Logger $logger,
        OrderHelper $orderHelper
    ) {
        $this->logger = $logger;
        $this->orderHelper = $orderHelper;
    }

    /**
     * Build cart data for payload
     *
     * @param array $buildSubject
     * @return array[]
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $orderItems = $paymentDO->getOrder()->getItems();
        $items = $this->orderHelper->formatOrderItems($orderItems);
        return [
            'cart' => [
                'items' =>  $items
            ],
        ];
    }
}