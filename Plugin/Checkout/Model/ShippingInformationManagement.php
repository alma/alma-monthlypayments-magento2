<?php

namespace Alma\MonthlyPayments\Plugin\Checkout\Model;


class ShippingInformationManagement
{
    /**
     * ShippingInformationManagement constructor.
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
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    )
    {
        $quote = $this->quoteFactory->create()->load($cartId);
        if ($quote) {
            $this->checkoutSession->setQuoteId($quote->getId());
        }
    }
}


