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

use Alma\MonthlyPayments\Helpers\AlmaClient;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class ReturnAction extends Action
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /** @var \Alma\API\Client */
    private $alma;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        QuoteRepository $quoteRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AlmaClient $almaClient
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->alma = $almaClient->getDefaultClient();
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $error = false;
        $quoteId = null;
        $orderId = null;
        $orderIncrementId = null;

        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order) {
            try {
                $almaPayment = $this->alma->payments->fetch($this->getRequest()->getParam('pid'));
            } catch (\Exception $e) {
                $error = true;
            }

            if ($almaPayment) {
                $quoteId = $almaPayment->custom_data["quote_id"];
                $orderIncrementId = $almaPayment->custom_data["order_id"];

                /** @var Order $order */
                $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $orderIncrementId, 'eq')->create();
                $order = $this->orderRepository->getList($searchCriteria)->getFirstItem();
                $orderId = $order->getId();
            } else {
                $error = true;
            }
        } else {
            $orderId = $order->getId();
            $orderIncrementId = $order->getIncrementId();
            $quoteId = $order->getQuoteId();
        }

        $this->checkoutSession->clearHelperData();

        if ($error) {
            $this->addWarning(__('Your order has been processed correctly but there was an error restoring your user session. You will find your order in your account.'));
        } else {
            $quote = $this->quoteRepository->get($quoteId);
            $quote->setIsActive(false);
            $this->quoteRepository->save($quote);

            $this->checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId)->setLastOrderId($orderId)->setLastRealOrderId($orderIncrementId);
        }

        return $this->_redirect('checkout/onepage/success');
    }

    private function addWarning($message)
    {
        if (method_exists($this->messageManager, 'addWarningMessage') && is_callable([$this->messageManager, 'addWarningMessage'])) {
            $this->messageManager->addWarningMessage($message);
        } else {
            $this->messageManager->addWarning($message);
        }
    }
}
