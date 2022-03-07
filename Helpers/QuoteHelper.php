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

namespace Alma\MonthlyPayments\Helpers;

use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Checkout\Model\Session;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Quote\Model\QuoteRepository;

class QuoteHelper
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @param Logger $logger
     * @param UserContextInterface $userContext
     * @param QuoteRepository $quoteRepository
     * @param Session $checkoutSession
     */
    public function __construct(
        Logger $logger,
        UserContextInterface $userContext,
        QuoteRepository $quoteRepository,
        Session $checkoutSession
    )
    {
        $this->logger = $logger;
        $this->userContext = $userContext;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param int|null $cartId
     * @return \Magento\Quote\Model\Quote|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuote($cartId=null):?\Magento\Quote\Model\Quote
    {
        $quote = null;
        $quoteById = $this->getQuoteById($cartId);
        if (isset($quoteById)){
            return $quoteById;
        }
        $contextUserQuote = $this->getQuoteByContextUserId();
        if(isset($contextUserQuote)){
            return $contextUserQuote;
        }

        $sessionQuote = $this->getQuoteFromSession();
        if (isset($sessionQuote)){
            return $sessionQuote;
        }
        return $quote;
    }

    /**
     * Get quote from session if is define
     * @return \Magento\Quote\Model\Quote|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQuoteFromSession():?\Magento\Quote\Model\Quote
    {
        $quote = null;
        $quoteId = $this->getQuoteIdFromSession();
        if($quoteId != null){
            $quote = $this->checkoutSession->getQuote();
        }
        return $quote;
    }


    /**
     * @return \Magento\Quote\Model\Quote|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuoteByContextUserId():?\Magento\Quote\Model\Quote
    {
        $quote = null;
        $customerUserId = $this->getContextUserId();
        if($customerUserId > 0){
            $quote = $this->quoteRepository->getActiveForCustomer($customerUserId);
        }
        return $quote;
    }

    /**
     * Load quote with cartID
     * @param $cartId
     * @return \Magento\Quote\Model\Quote\Interceptor|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuoteById($cartId):?\Magento\Quote\Model\Quote\Interceptor
    {
        $quote = null;
        try {
            $quote = $this->quoteRepository->get($cartId);
        } catch (\Exception $e) {
            $this->logger->info('getQuoteById Exeption : ',[$e->getMessage()]);
        }
        return $quote;
    }

    /**
     * @return int|null
     * default value 0
     */
    private function getContextUserId():?int
    {
        return $this->userContext->getUserId();
    }

    /**
     * @return int|null
     */
    private function getQuoteIdFromSession():?int
    {
        return $this->checkoutSession->getQuoteId();
    }

    /**
     * @param $quote
     * @return void
     */
    public function setQuoteInSession($quote):void
    {
        if (!$this->checkoutSession->hasQuote() && $quote) {
            $this->checkoutSession->replaceQuote($quote);
        }
    }


}
