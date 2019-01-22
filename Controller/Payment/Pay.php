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

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteRepository;

class Pay extends Action
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        QuoteRepository $quoteRepository
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order) {
            throw new LocalizedException(__('Error: cannot find order in session'));
        }

        $payment = $order->getPayment();
        if (!$payment) {
            throw new LocalizedException(__('Error getting payment information from session'));
        }

        $url = $payment->getAdditionalInformation()['PAYMENT_URL'];
        if (empty($url)) {
            throw new LocalizedException(__('Error: no payment URL found in session'));
        }

        // Keep quote active in case the customer comes back to the site without paying
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $quote->setIsActive(true);
        $this->checkoutSession->restoreQuote();
        $this->quoteRepository->save($quote);

        $this->checkoutSession->setLastQuoteId($quote->getId())->setLastSuccessQuoteId($quote->getId())->setLastOrderId($order->getId())->setLastRealOrderId($order->getIncrementId());

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($url);
        return $redirect;
    }
}
