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

use Alma\MonthlyPayments\Helpers\PaymentValidation;
use Alma\MonthlyPayments\Model\Exceptions\AlmaPaymentValidationException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class ReturnAction extends Action
{
    /**
     * @var PaymentValidation
     */
    private $paymentValidationHelper;

    /**
     * ReturnAction constructor.
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
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $paymentId = $this->getRequest()->getParam('pid');
            $this->paymentValidationHelper->completeOrderIfValid($paymentId);
            $redirectTo = 'checkout/onepage/success';
        } catch (AlmaPaymentValidationException $e) {
            $this->addError($e->getMessage());
            $redirectTo = $e->getReturnPath();
        }

        return $this->_redirect($redirectTo);
    }

    private function addError($message)
    {
        if (method_exists($this->messageManager, 'addErrorMessage') && is_callable([$this->messageManager, 'addErrorMessage'])) {
            $this->messageManager->addErrorMessage($message);
        } else {
            $this->messageManager->addError($message);
        }
    }
}
