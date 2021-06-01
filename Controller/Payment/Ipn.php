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

use Alma\MonthlyPayments\Helpers\AlmaPaymentValidationError;
use Alma\MonthlyPayments\Helpers\PaymentValidation;
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
     * Ipn constructor.
     * @param Context $context
     * @param PaymentValidation $paymentValidationHelper
     */
    public function __construct(
        Context $context,
        PaymentValidation $paymentValidationHelper
    )
    {
        parent::__construct($context);
        $this->paymentValidationHelper = $paymentValidationHelper;
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var Json $json */
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $paymentId = $this->getRequest()->getParam('pid');
            $this->paymentValidationHelper->completeOrderIfValid($paymentId);
        } catch (AlmaPaymentValidationError $e) {
            return $json->setData(["error" => $e->getMessage()])->setHttpResponseCode(500);
        }

        return $json->setData(["success" => true])->setHttpResponseCode(200);
    }
}
