<?php

namespace Alma\MonthlyPayments\Plugin\Checkout\Model;


class ShippingInformationManagement
{

    public function __construct(
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
    }

    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        $quote = $this->quoteFactory->create()->load($cartId);
        if ($quote) {
            $this->checkoutSession->setQuoteId($quote->getId());
        }
    }
} 


