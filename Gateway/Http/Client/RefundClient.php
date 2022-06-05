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

namespace Alma\MonthlyPayments\Gateway\Http\Client;

use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Functions;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class RefundClient implements ClientInterface
{

    /**
     * @var Logger
     */
    private $logger;

    /** @var \Alma\API\Client|null */
    private $alma;

    /**
     * @param Logger $logger
     * @param AlmaClient $almaClient
     */
    public function __construct(
        Logger $logger,
        AlmaClient $almaClient
    ) {
        $this->logger = $logger;
        $this->alma = $almaClient->getDefaultClient();
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        try {
            $payloadData = $transferObject->getBody();
            $refund = $this->alma->payments->partialRefund($payloadData['payment_id'], Functions::PriceToCents($payloadData['amount']), $payloadData['merchant_id'], 'Refund with Magento 2 module');
        } catch (RequestError $e) {
            $this->logger->error("Error creating refund:", [$e]);
            return [
                'resultCode' => 0,
                'fails' => $e->response,
            ];
        }
        return [
            'resultCode' => 1,
            'almaRefund' => $refund,
        ];
    }
}
