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

use Alma\API\Lib\PaymentValidator;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\PaymentValidation;
use Alma\MonthlyPayments\Model\Exceptions\AlmaPaymentValidationException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Ipn extends Action
{

    /**
     * @var PaymentValidation
     */
    private $paymentValidationHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var PaymentValidator
     */
    private $paymentValidator;

    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;

    /**
     * @param Context $context
     * @param PaymentValidation $paymentValidationHelper
     * @param Logger $logger
     * @param PaymentValidator $paymentValidator
     * @param ApiConfigHelper $apiConfigHelper
     */
    public function __construct(
        Context           $context,
        PaymentValidation $paymentValidationHelper,
        Logger            $logger,
        PaymentValidator  $paymentValidator,
        ApiConfigHelper   $apiConfigHelper
    ) {
        parent::__construct($context);
        $this->paymentValidationHelper = $paymentValidationHelper;
        $this->logger = $logger;
        $this->paymentValidator = $paymentValidator;
        $this->apiConfigHelper = $apiConfigHelper;
    }


    /**
     * @inerhitDoc
     */
    public function execute(): Json
    {
        $paymentId = $this->getRequest()->getParam('pid');

        try {
            $apiKey = $this->getApiKey();
            $this->checkSignature($paymentId, $apiKey);
            $this->paymentValidationHelper->completeOrderIfValid($paymentId);
        } catch (AlmaPaymentValidationException $e) {
            return $this->setHttpResponse(["error" => $e->getMessage()], $e->getCode());
        }
        return $this->setHttpResponse(["success" => true], 200);
    }

    /**
     * Get Active API KEY, if not found throw exception
     *
     * @return string
     * @throws AlmaPaymentValidationException
     */
    private function getApiKey(): string
    {
        $apiKey = $this->apiConfigHelper->getActiveAPIKey();
        if (!$apiKey) {
            $this->logger->error("Missing API key in IPN request");
            throw new AlmaPaymentValidationException("Missing API key in IPN request", '', 500);
        }
        return $apiKey;
    }

    /**
     * Check Alma payment signature
     *
     * @param string $paymentId
     * @param string $apiKey
     * @return void
     * @throws AlmaPaymentValidationException
     */
    private function checkSignature(string $paymentId, string $apiKey): void
    {
        $signature = $this->getRequest()->getHeader(PaymentValidator::HEADER_SIGNATURE_KEY);
        if (!$signature) {
            $this->logger->error("Missing signature in IPN request");
            throw new AlmaPaymentValidationException("Missing signature", '', 401);
        }

        if (!$this->paymentValidator->isHmacValidated($paymentId, $apiKey, $signature)) {
            $this->logger->error("Wrong signature in IPN request", [
                'payment_id' => $paymentId,
                'signature' => $signature
            ]);
            throw new AlmaPaymentValidationException("Wrong signature in IPN request", '', 401);
        }
    }

    /**
     * Set data and code in response
     *
     * @param array $data
     * @param int $code
     * @return Json
     */
    private function setHttpResponse(array $data, int $code): Json
    {
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($data)->setHttpResponseCode($code);
    }
}
