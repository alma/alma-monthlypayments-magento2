<?php

namespace Alma\MonthlyPayments\Plugin\Quote\Model;


class PaymentMethodManagement
{
    /**
     * PaymentMethodManagement constructor.
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Quote\Model\PaymentMethodManagement $subject
     * @param $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $method
     */
    public function beforeSet(
        \Magento\Quote\Model\PaymentMethodManagement $subject,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $method
    )
    {
        $quote = $this->quoteFactory->create()->load($cartId);
        if ($quote) {
            $this->checkoutSession->setQuoteId($quote->getId());
        }
    }
}


