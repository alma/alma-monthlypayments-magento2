<?php

namespace Alma\MonthlyPayments\Plugin\Quote\Model;


class PaymentMethodManagement
{

    public function __construct(
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
    }

    public function beforeSet(
        \Magento\Quote\Model\PaymentMethodManagement $subject,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $method
    ) {
        $quote = $this->quoteFactory->create()->load($cartId);
        if ($quote) {
            $this->checkoutSession->setQuoteId($quote->getId());
        }
    }
} 


