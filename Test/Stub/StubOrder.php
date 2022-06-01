<?php

namespace Alma\MonthlyPayments\Test\Stub;

use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class StubOrder
 *
 * @package Alma\MonthlyPayments\Test\Unit\Helpers\ShareOfCheckout
 */
class StubOrder implements OrderInterface
{

    private $payment;
    private $currencyCode;

    public function __construct($currencyCode, $paymentAmountPaid, $paymentAmountRefunded, $paymentMethodCode)
    {
        $this->currencyCode = $currencyCode;
        $this->payment = new StubPayment($paymentAmountPaid, $paymentAmountRefunded, $paymentMethodCode);
    }

    public function getAdjustmentNegative()
    {
        // TODO: Implement getAdjustmentNegative() method.
    }

    public function getAdjustmentPositive()
    {
        // TODO: Implement getAdjustmentPositive() method.
    }

    public function getAppliedRuleIds()
    {
        // TODO: Implement getAppliedRuleIds() method.
    }

    public function getBaseAdjustmentNegative()
    {
        // TODO: Implement getBaseAdjustmentNegative() method.
    }

    public function getBaseAdjustmentPositive()
    {
        // TODO: Implement getBaseAdjustmentPositive() method.
    }

    public function getBaseCurrencyCode()
    {
        // TODO: Implement getBaseCurrencyCode() method.
    }

    public function getBaseDiscountAmount()
    {
        // TODO: Implement getBaseDiscountAmount() method.
    }

    public function getBaseDiscountCanceled()
    {
        // TODO: Implement getBaseDiscountCanceled() method.
    }

    public function getBaseDiscountInvoiced()
    {
        // TODO: Implement getBaseDiscountInvoiced() method.
    }

    public function getBaseDiscountRefunded()
    {
        // TODO: Implement getBaseDiscountRefunded() method.
    }

    public function getBaseGrandTotal()
    {
        // TODO: Implement getBaseGrandTotal() method.
    }

    public function getBaseDiscountTaxCompensationAmount()
    {
        // TODO: Implement getBaseDiscountTaxCompensationAmount() method.
    }

    public function getBaseDiscountTaxCompensationInvoiced()
    {
        // TODO: Implement getBaseDiscountTaxCompensationInvoiced() method.
    }

    public function getBaseDiscountTaxCompensationRefunded()
    {
        // TODO: Implement getBaseDiscountTaxCompensationRefunded() method.
    }

    public function getBaseShippingAmount()
    {
        // TODO: Implement getBaseShippingAmount() method.
    }

    public function getBaseShippingCanceled()
    {
        // TODO: Implement getBaseShippingCanceled() method.
    }

    public function getBaseShippingDiscountAmount()
    {
        // TODO: Implement getBaseShippingDiscountAmount() method.
    }

    public function getBaseShippingDiscountTaxCompensationAmnt()
    {
        // TODO: Implement getBaseShippingDiscountTaxCompensationAmnt() method.
    }

    public function getBaseShippingInclTax()
    {
        // TODO: Implement getBaseShippingInclTax() method.
    }

    public function getBaseShippingInvoiced()
    {
        // TODO: Implement getBaseShippingInvoiced() method.
    }

    public function getBaseShippingRefunded()
    {
        // TODO: Implement getBaseShippingRefunded() method.
    }

    public function getBaseShippingTaxAmount()
    {
        // TODO: Implement getBaseShippingTaxAmount() method.
    }

    public function getBaseShippingTaxRefunded()
    {
        // TODO: Implement getBaseShippingTaxRefunded() method.
    }

    public function getBaseSubtotal()
    {
        // TODO: Implement getBaseSubtotal() method.
    }

    public function getBaseSubtotalCanceled()
    {
        // TODO: Implement getBaseSubtotalCanceled() method.
    }

    public function getBaseSubtotalInclTax()
    {
        // TODO: Implement getBaseSubtotalInclTax() method.
    }

    public function getBaseSubtotalInvoiced()
    {
        // TODO: Implement getBaseSubtotalInvoiced() method.
    }

    public function getBaseSubtotalRefunded()
    {
        // TODO: Implement getBaseSubtotalRefunded() method.
    }

    public function getBaseTaxAmount()
    {
        // TODO: Implement getBaseTaxAmount() method.
    }

    public function getBaseTaxCanceled()
    {
        // TODO: Implement getBaseTaxCanceled() method.
    }

    public function getBaseTaxInvoiced()
    {
        // TODO: Implement getBaseTaxInvoiced() method.
    }

    public function getBaseTaxRefunded()
    {
        // TODO: Implement getBaseTaxRefunded() method.
    }

    public function getBaseTotalCanceled()
    {
        // TODO: Implement getBaseTotalCanceled() method.
    }

    public function getBaseTotalDue()
    {
        // TODO: Implement getBaseTotalDue() method.
    }

    public function getBaseTotalInvoiced()
    {
        // TODO: Implement getBaseTotalInvoiced() method.
    }

    public function getBaseTotalInvoicedCost()
    {
        // TODO: Implement getBaseTotalInvoicedCost() method.
    }

    public function getBaseTotalOfflineRefunded()
    {
        // TODO: Implement getBaseTotalOfflineRefunded() method.
    }

    public function getBaseTotalOnlineRefunded()
    {
        // TODO: Implement getBaseTotalOnlineRefunded() method.
    }

    public function getBaseTotalPaid()
    {
        // TODO: Implement getBaseTotalPaid() method.
    }

    public function getBaseTotalQtyOrdered()
    {
        // TODO: Implement getBaseTotalQtyOrdered() method.
    }

    public function getBaseTotalRefunded()
    {
        // TODO: Implement getBaseTotalRefunded() method.
    }

    public function getBaseToGlobalRate()
    {
        // TODO: Implement getBaseToGlobalRate() method.
    }

    public function getBaseToOrderRate()
    {
        // TODO: Implement getBaseToOrderRate() method.
    }

    public function getBillingAddressId()
    {
        // TODO: Implement getBillingAddressId() method.
    }

    public function getCanShipPartially()
    {
        // TODO: Implement getCanShipPartially() method.
    }

    public function getCanShipPartiallyItem()
    {
        // TODO: Implement getCanShipPartiallyItem() method.
    }

    public function getCouponCode()
    {
        // TODO: Implement getCouponCode() method.
    }

    public function getCreatedAt()
    {
        // TODO: Implement getCreatedAt() method.
    }

    public function setCreatedAt($createdAt)
    {
        // TODO: Implement setCreatedAt() method.
    }

    public function getCustomerDob()
    {
        // TODO: Implement getCustomerDob() method.
    }

    public function getCustomerEmail()
    {
        // TODO: Implement getCustomerEmail() method.
    }

    public function getCustomerFirstname()
    {
        // TODO: Implement getCustomerFirstname() method.
    }

    public function getCustomerGender()
    {
        // TODO: Implement getCustomerGender() method.
    }

    public function getCustomerGroupId()
    {
        // TODO: Implement getCustomerGroupId() method.
    }

    public function getCustomerId()
    {
        // TODO: Implement getCustomerId() method.
    }

    public function getCustomerIsGuest()
    {
        // TODO: Implement getCustomerIsGuest() method.
    }

    public function getCustomerLastname()
    {
        // TODO: Implement getCustomerLastname() method.
    }

    public function getCustomerMiddlename()
    {
        // TODO: Implement getCustomerMiddlename() method.
    }

    public function getCustomerNote()
    {
        // TODO: Implement getCustomerNote() method.
    }

    public function getCustomerNoteNotify()
    {
        // TODO: Implement getCustomerNoteNotify() method.
    }

    public function getCustomerPrefix()
    {
        // TODO: Implement getCustomerPrefix() method.
    }

    public function getCustomerSuffix()
    {
        // TODO: Implement getCustomerSuffix() method.
    }

    public function getCustomerTaxvat()
    {
        // TODO: Implement getCustomerTaxvat() method.
    }

    public function getDiscountAmount()
    {
        // TODO: Implement getDiscountAmount() method.
    }

    public function getDiscountCanceled()
    {
        // TODO: Implement getDiscountCanceled() method.
    }

    public function getDiscountDescription()
    {
        // TODO: Implement getDiscountDescription() method.
    }

    public function getDiscountInvoiced()
    {
        // TODO: Implement getDiscountInvoiced() method.
    }

    public function getDiscountRefunded()
    {
        // TODO: Implement getDiscountRefunded() method.
    }

    public function getEditIncrement()
    {
        // TODO: Implement getEditIncrement() method.
    }

    public function getEmailSent()
    {
        // TODO: Implement getEmailSent() method.
    }

    public function getEntityId()
    {
        // TODO: Implement getEntityId() method.
    }

    public function setEntityId($entityId)
    {
        // TODO: Implement setEntityId() method.
    }

    public function getExtCustomerId()
    {
        // TODO: Implement getExtCustomerId() method.
    }

    public function getExtOrderId()
    {
        // TODO: Implement getExtOrderId() method.
    }

    public function getForcedShipmentWithInvoice()
    {
        // TODO: Implement getForcedShipmentWithInvoice() method.
    }

    public function getGlobalCurrencyCode()
    {
        // TODO: Implement getGlobalCurrencyCode() method.
    }

    public function getGrandTotal()
    {
        // TODO: Implement getGrandTotal() method.
    }

    public function getDiscountTaxCompensationAmount()
    {
        // TODO: Implement getDiscountTaxCompensationAmount() method.
    }

    public function getDiscountTaxCompensationInvoiced()
    {
        // TODO: Implement getDiscountTaxCompensationInvoiced() method.
    }

    public function getDiscountTaxCompensationRefunded()
    {
        // TODO: Implement getDiscountTaxCompensationRefunded() method.
    }

    public function getHoldBeforeState()
    {
        // TODO: Implement getHoldBeforeState() method.
    }

    public function getHoldBeforeStatus()
    {
        // TODO: Implement getHoldBeforeStatus() method.
    }

    public function getIncrementId()
    {
        // TODO: Implement getIncrementId() method.
    }

    public function getIsVirtual()
    {
        // TODO: Implement getIsVirtual() method.
    }

    public function getOrderCurrencyCode()
    {
        return $this->currencyCode;
    }

    public function getOriginalIncrementId()
    {
        // TODO: Implement getOriginalIncrementId() method.
    }

    public function getPaymentAuthorizationAmount()
    {
        // TODO: Implement getPaymentAuthorizationAmount() method.
    }

    public function getPaymentAuthExpiration()
    {
        // TODO: Implement getPaymentAuthExpiration() method.
    }

    public function getProtectCode()
    {
        // TODO: Implement getProtectCode() method.
    }

    public function getQuoteAddressId()
    {
        // TODO: Implement getQuoteAddressId() method.
    }

    public function getQuoteId()
    {
        // TODO: Implement getQuoteId() method.
    }

    public function getRelationChildId()
    {
        // TODO: Implement getRelationChildId() method.
    }

    public function getRelationChildRealId()
    {
        // TODO: Implement getRelationChildRealId() method.
    }

    public function getRelationParentId()
    {
        // TODO: Implement getRelationParentId() method.
    }

    public function getRelationParentRealId()
    {
        // TODO: Implement getRelationParentRealId() method.
    }

    public function getRemoteIp()
    {
        // TODO: Implement getRemoteIp() method.
    }

    public function getShippingAmount()
    {
        // TODO: Implement getShippingAmount() method.
    }

    public function getShippingCanceled()
    {
        // TODO: Implement getShippingCanceled() method.
    }

    public function getShippingDescription()
    {
        // TODO: Implement getShippingDescription() method.
    }

    public function getShippingDiscountAmount()
    {
        // TODO: Implement getShippingDiscountAmount() method.
    }

    public function getShippingDiscountTaxCompensationAmount()
    {
        // TODO: Implement getShippingDiscountTaxCompensationAmount() method.
    }

    public function getShippingInclTax()
    {
        // TODO: Implement getShippingInclTax() method.
    }

    public function getShippingInvoiced()
    {
        // TODO: Implement getShippingInvoiced() method.
    }

    public function getShippingRefunded()
    {
        // TODO: Implement getShippingRefunded() method.
    }

    public function getShippingTaxAmount()
    {
        // TODO: Implement getShippingTaxAmount() method.
    }

    public function getShippingTaxRefunded()
    {
        // TODO: Implement getShippingTaxRefunded() method.
    }

    public function getState()
    {
        // TODO: Implement getState() method.
    }

    public function getStatus()
    {
        // TODO: Implement getStatus() method.
    }

    public function getStoreCurrencyCode()
    {
        // TODO: Implement getStoreCurrencyCode() method.
    }

    public function getStoreId()
    {
        // TODO: Implement getStoreId() method.
    }

    public function getStoreName()
    {
        // TODO: Implement getStoreName() method.
    }

    public function getStoreToBaseRate()
    {
        // TODO: Implement getStoreToBaseRate() method.
    }

    public function getStoreToOrderRate()
    {
        // TODO: Implement getStoreToOrderRate() method.
    }

    public function getSubtotal()
    {
        // TODO: Implement getSubtotal() method.
    }

    public function getSubtotalCanceled()
    {
        // TODO: Implement getSubtotalCanceled() method.
    }

    public function getSubtotalInclTax()
    {
        // TODO: Implement getSubtotalInclTax() method.
    }

    public function getSubtotalInvoiced()
    {
        // TODO: Implement getSubtotalInvoiced() method.
    }

    public function getSubtotalRefunded()
    {
        // TODO: Implement getSubtotalRefunded() method.
    }

    public function getTaxAmount()
    {
        // TODO: Implement getTaxAmount() method.
    }

    public function getTaxCanceled()
    {
        // TODO: Implement getTaxCanceled() method.
    }

    public function getTaxInvoiced()
    {
        // TODO: Implement getTaxInvoiced() method.
    }

    public function getTaxRefunded()
    {
        // TODO: Implement getTaxRefunded() method.
    }

    public function getTotalCanceled()
    {
        // TODO: Implement getTotalCanceled() method.
    }

    public function getTotalDue()
    {
        // TODO: Implement getTotalDue() method.
    }

    public function getTotalInvoiced()
    {
        // TODO: Implement getTotalInvoiced() method.
    }

    public function getTotalItemCount()
    {
        // TODO: Implement getTotalItemCount() method.
    }

    public function getTotalOfflineRefunded()
    {
        // TODO: Implement getTotalOfflineRefunded() method.
    }

    public function getTotalOnlineRefunded()
    {
        // TODO: Implement getTotalOnlineRefunded() method.
    }

    public function getTotalPaid()
    {
        // TODO: Implement getTotalPaid() method.
    }

    public function getTotalQtyOrdered()
    {
        // TODO: Implement getTotalQtyOrdered() method.
    }

    public function getTotalRefunded()
    {
        // TODO: Implement getTotalRefunded() method.
    }

    public function getUpdatedAt()
    {
        // TODO: Implement getUpdatedAt() method.
    }

    public function getWeight()
    {
        // TODO: Implement getWeight() method.
    }

    public function getXForwardedFor()
    {
        // TODO: Implement getXForwardedFor() method.
    }

    public function getItems()
    {
        // TODO: Implement getItems() method.
    }

    public function setItems($items)
    {
        // TODO: Implement setItems() method.
    }

    public function getBillingAddress()
    {
        // TODO: Implement getBillingAddress() method.
    }

    public function setBillingAddress(OrderAddressInterface $billingAddress = null)
    {
        // TODO: Implement setBillingAddress() method.
    }

    public function getPayment()
    {
        return $this->payment;
    }

    public function setPayment(OrderPaymentInterface $payment = null)
    {
        // TODO: Implement setPayment() method.
    }

    public function getStatusHistories()
    {
        // TODO: Implement getStatusHistories() method.
    }

    public function setStatusHistories(array $statusHistories = null)
    {
        // TODO: Implement setStatusHistories() method.
    }

    public function setState($state)
    {
        // TODO: Implement setState() method.
    }

    public function setStatus($status)
    {
        // TODO: Implement setStatus() method.
    }

    public function setCouponCode($code)
    {
        // TODO: Implement setCouponCode() method.
    }

    public function setProtectCode($code)
    {
        // TODO: Implement setProtectCode() method.
    }

    public function setShippingDescription($description)
    {
        // TODO: Implement setShippingDescription() method.
    }

    public function setIsVirtual($isVirtual)
    {
        // TODO: Implement setIsVirtual() method.
    }

    public function setStoreId($id)
    {
        // TODO: Implement setStoreId() method.
    }

    public function setCustomerId($id)
    {
        // TODO: Implement setCustomerId() method.
    }

    public function setBaseDiscountAmount($amount)
    {
        // TODO: Implement setBaseDiscountAmount() method.
    }

    public function setBaseDiscountCanceled($baseDiscountCanceled)
    {
        // TODO: Implement setBaseDiscountCanceled() method.
    }

    public function setBaseDiscountInvoiced($baseDiscountInvoiced)
    {
        // TODO: Implement setBaseDiscountInvoiced() method.
    }

    public function setBaseDiscountRefunded($baseDiscountRefunded)
    {
        // TODO: Implement setBaseDiscountRefunded() method.
    }

    public function setBaseGrandTotal($amount)
    {
        // TODO: Implement setBaseGrandTotal() method.
    }

    public function setBaseShippingAmount($amount)
    {
        // TODO: Implement setBaseShippingAmount() method.
    }

    public function setBaseShippingCanceled($baseShippingCanceled)
    {
        // TODO: Implement setBaseShippingCanceled() method.
    }

    public function setBaseShippingInvoiced($baseShippingInvoiced)
    {
        // TODO: Implement setBaseShippingInvoiced() method.
    }

    public function setBaseShippingRefunded($baseShippingRefunded)
    {
        // TODO: Implement setBaseShippingRefunded() method.
    }

    public function setBaseShippingTaxAmount($amount)
    {
        // TODO: Implement setBaseShippingTaxAmount() method.
    }

    public function setBaseShippingTaxRefunded($baseShippingTaxRefunded)
    {
        // TODO: Implement setBaseShippingTaxRefunded() method.
    }

    public function setBaseSubtotal($amount)
    {
        // TODO: Implement setBaseSubtotal() method.
    }

    public function setBaseSubtotalCanceled($baseSubtotalCanceled)
    {
        // TODO: Implement setBaseSubtotalCanceled() method.
    }

    public function setBaseSubtotalInvoiced($baseSubtotalInvoiced)
    {
        // TODO: Implement setBaseSubtotalInvoiced() method.
    }

    public function setBaseSubtotalRefunded($baseSubtotalRefunded)
    {
        // TODO: Implement setBaseSubtotalRefunded() method.
    }

    public function setBaseTaxAmount($amount)
    {
        // TODO: Implement setBaseTaxAmount() method.
    }

    public function setBaseTaxCanceled($baseTaxCanceled)
    {
        // TODO: Implement setBaseTaxCanceled() method.
    }

    public function setBaseTaxInvoiced($baseTaxInvoiced)
    {
        // TODO: Implement setBaseTaxInvoiced() method.
    }

    public function setBaseTaxRefunded($baseTaxRefunded)
    {
        // TODO: Implement setBaseTaxRefunded() method.
    }

    public function setBaseToGlobalRate($rate)
    {
        // TODO: Implement setBaseToGlobalRate() method.
    }

    public function setBaseToOrderRate($rate)
    {
        // TODO: Implement setBaseToOrderRate() method.
    }

    public function setBaseTotalCanceled($baseTotalCanceled)
    {
        // TODO: Implement setBaseTotalCanceled() method.
    }

    public function setBaseTotalInvoiced($baseTotalInvoiced)
    {
        // TODO: Implement setBaseTotalInvoiced() method.
    }

    public function setBaseTotalInvoicedCost($baseTotalInvoicedCost)
    {
        // TODO: Implement setBaseTotalInvoicedCost() method.
    }

    public function setBaseTotalOfflineRefunded($baseTotalOfflineRefunded)
    {
        // TODO: Implement setBaseTotalOfflineRefunded() method.
    }

    public function setBaseTotalOnlineRefunded($baseTotalOnlineRefunded)
    {
        // TODO: Implement setBaseTotalOnlineRefunded() method.
    }

    public function setBaseTotalPaid($baseTotalPaid)
    {
        // TODO: Implement setBaseTotalPaid() method.
    }

    public function setBaseTotalQtyOrdered($baseTotalQtyOrdered)
    {
        // TODO: Implement setBaseTotalQtyOrdered() method.
    }

    public function setBaseTotalRefunded($baseTotalRefunded)
    {
        // TODO: Implement setBaseTotalRefunded() method.
    }

    public function setDiscountAmount($amount)
    {
        // TODO: Implement setDiscountAmount() method.
    }

    public function setDiscountCanceled($discountCanceled)
    {
        // TODO: Implement setDiscountCanceled() method.
    }

    public function setDiscountInvoiced($discountInvoiced)
    {
        // TODO: Implement setDiscountInvoiced() method.
    }

    public function setDiscountRefunded($discountRefunded)
    {
        // TODO: Implement setDiscountRefunded() method.
    }

    public function setGrandTotal($amount)
    {
        // TODO: Implement setGrandTotal() method.
    }

    public function setShippingAmount($amount)
    {
        // TODO: Implement setShippingAmount() method.
    }

    public function setShippingCanceled($shippingCanceled)
    {
        // TODO: Implement setShippingCanceled() method.
    }

    public function setShippingInvoiced($shippingInvoiced)
    {
        // TODO: Implement setShippingInvoiced() method.
    }

    public function setShippingRefunded($shippingRefunded)
    {
        // TODO: Implement setShippingRefunded() method.
    }

    public function setShippingTaxAmount($amount)
    {
        // TODO: Implement setShippingTaxAmount() method.
    }

    public function setShippingTaxRefunded($shippingTaxRefunded)
    {
        // TODO: Implement setShippingTaxRefunded() method.
    }

    public function setStoreToBaseRate($rate)
    {
        // TODO: Implement setStoreToBaseRate() method.
    }

    public function setStoreToOrderRate($rate)
    {
        // TODO: Implement setStoreToOrderRate() method.
    }

    public function setSubtotal($amount)
    {
        // TODO: Implement setSubtotal() method.
    }

    public function setSubtotalCanceled($subtotalCanceled)
    {
        // TODO: Implement setSubtotalCanceled() method.
    }

    public function setSubtotalInvoiced($subtotalInvoiced)
    {
        // TODO: Implement setSubtotalInvoiced() method.
    }

    public function setSubtotalRefunded($subtotalRefunded)
    {
        // TODO: Implement setSubtotalRefunded() method.
    }

    public function setTaxAmount($amount)
    {
        // TODO: Implement setTaxAmount() method.
    }

    public function setTaxCanceled($taxCanceled)
    {
        // TODO: Implement setTaxCanceled() method.
    }

    public function setTaxInvoiced($taxInvoiced)
    {
        // TODO: Implement setTaxInvoiced() method.
    }

    public function setTaxRefunded($taxRefunded)
    {
        // TODO: Implement setTaxRefunded() method.
    }

    public function setTotalCanceled($totalCanceled)
    {
        // TODO: Implement setTotalCanceled() method.
    }

    public function setTotalInvoiced($totalInvoiced)
    {
        // TODO: Implement setTotalInvoiced() method.
    }

    public function setTotalOfflineRefunded($totalOfflineRefunded)
    {
        // TODO: Implement setTotalOfflineRefunded() method.
    }

    public function setTotalOnlineRefunded($totalOnlineRefunded)
    {
        // TODO: Implement setTotalOnlineRefunded() method.
    }

    public function setTotalPaid($totalPaid)
    {
        // TODO: Implement setTotalPaid() method.
    }

    public function setTotalQtyOrdered($totalQtyOrdered)
    {
        // TODO: Implement setTotalQtyOrdered() method.
    }

    public function setTotalRefunded($totalRefunded)
    {
        // TODO: Implement setTotalRefunded() method.
    }

    public function setCanShipPartially($flag)
    {
        // TODO: Implement setCanShipPartially() method.
    }

    public function setCanShipPartiallyItem($flag)
    {
        // TODO: Implement setCanShipPartiallyItem() method.
    }

    public function setCustomerIsGuest($customerIsGuest)
    {
        // TODO: Implement setCustomerIsGuest() method.
    }

    public function setCustomerNoteNotify($customerNoteNotify)
    {
        // TODO: Implement setCustomerNoteNotify() method.
    }

    public function setBillingAddressId($id)
    {
        // TODO: Implement setBillingAddressId() method.
    }

    public function setCustomerGroupId($id)
    {
        // TODO: Implement setCustomerGroupId() method.
    }

    public function setEditIncrement($editIncrement)
    {
        // TODO: Implement setEditIncrement() method.
    }

    public function setEmailSent($emailSent)
    {
        // TODO: Implement setEmailSent() method.
    }

    public function setForcedShipmentWithInvoice($forcedShipmentWithInvoice)
    {
        // TODO: Implement setForcedShipmentWithInvoice() method.
    }

    public function setPaymentAuthExpiration($paymentAuthExpiration)
    {
        // TODO: Implement setPaymentAuthExpiration() method.
    }

    public function setQuoteAddressId($id)
    {
        // TODO: Implement setQuoteAddressId() method.
    }

    public function setQuoteId($id)
    {
        // TODO: Implement setQuoteId() method.
    }

    public function setAdjustmentNegative($adjustmentNegative)
    {
        // TODO: Implement setAdjustmentNegative() method.
    }

    public function setAdjustmentPositive($adjustmentPositive)
    {
        // TODO: Implement setAdjustmentPositive() method.
    }

    public function setBaseAdjustmentNegative($baseAdjustmentNegative)
    {
        // TODO: Implement setBaseAdjustmentNegative() method.
    }

    public function setBaseAdjustmentPositive($baseAdjustmentPositive)
    {
        // TODO: Implement setBaseAdjustmentPositive() method.
    }

    public function setBaseShippingDiscountAmount($amount)
    {
        // TODO: Implement setBaseShippingDiscountAmount() method.
    }

    public function setBaseSubtotalInclTax($amount)
    {
        // TODO: Implement setBaseSubtotalInclTax() method.
    }

    public function setBaseTotalDue($baseTotalDue)
    {
        // TODO: Implement setBaseTotalDue() method.
    }

    public function setPaymentAuthorizationAmount($amount)
    {
        // TODO: Implement setPaymentAuthorizationAmount() method.
    }

    public function setShippingDiscountAmount($amount)
    {
        // TODO: Implement setShippingDiscountAmount() method.
    }

    public function setSubtotalInclTax($amount)
    {
        // TODO: Implement setSubtotalInclTax() method.
    }

    public function setTotalDue($totalDue)
    {
        // TODO: Implement setTotalDue() method.
    }

    public function setWeight($weight)
    {
        // TODO: Implement setWeight() method.
    }

    public function setCustomerDob($customerDob)
    {
        // TODO: Implement setCustomerDob() method.
    }

    public function setIncrementId($id)
    {
        // TODO: Implement setIncrementId() method.
    }

    public function setAppliedRuleIds($appliedRuleIds)
    {
        // TODO: Implement setAppliedRuleIds() method.
    }

    public function setBaseCurrencyCode($code)
    {
        // TODO: Implement setBaseCurrencyCode() method.
    }

    public function setCustomerEmail($customerEmail)
    {
        // TODO: Implement setCustomerEmail() method.
    }

    public function setCustomerFirstname($customerFirstname)
    {
        // TODO: Implement setCustomerFirstname() method.
    }

    public function setCustomerLastname($customerLastname)
    {
        // TODO: Implement setCustomerLastname() method.
    }

    public function setCustomerMiddlename($customerMiddlename)
    {
        // TODO: Implement setCustomerMiddlename() method.
    }

    public function setCustomerPrefix($customerPrefix)
    {
        // TODO: Implement setCustomerPrefix() method.
    }

    public function setCustomerSuffix($customerSuffix)
    {
        // TODO: Implement setCustomerSuffix() method.
    }

    public function setCustomerTaxvat($customerTaxvat)
    {
        // TODO: Implement setCustomerTaxvat() method.
    }

    public function setDiscountDescription($description)
    {
        // TODO: Implement setDiscountDescription() method.
    }

    public function setExtCustomerId($id)
    {
        // TODO: Implement setExtCustomerId() method.
    }

    public function setExtOrderId($id)
    {
        // TODO: Implement setExtOrderId() method.
    }

    public function setGlobalCurrencyCode($code)
    {
        // TODO: Implement setGlobalCurrencyCode() method.
    }

    public function setHoldBeforeState($holdBeforeState)
    {
        // TODO: Implement setHoldBeforeState() method.
    }

    public function setHoldBeforeStatus($holdBeforeStatus)
    {
        // TODO: Implement setHoldBeforeStatus() method.
    }

    public function setOrderCurrencyCode($code)
    {
        // TODO: Implement setOrderCurrencyCode() method.
    }

    public function setOriginalIncrementId($id)
    {
        // TODO: Implement setOriginalIncrementId() method.
    }

    public function setRelationChildId($id)
    {
        // TODO: Implement setRelationChildId() method.
    }

    public function setRelationChildRealId($realId)
    {
        // TODO: Implement setRelationChildRealId() method.
    }

    public function setRelationParentId($id)
    {
        // TODO: Implement setRelationParentId() method.
    }

    public function setRelationParentRealId($realId)
    {
        // TODO: Implement setRelationParentRealId() method.
    }

    public function setRemoteIp($remoteIp)
    {
        // TODO: Implement setRemoteIp() method.
    }

    public function setStoreCurrencyCode($code)
    {
        // TODO: Implement setStoreCurrencyCode() method.
    }

    public function setStoreName($storeName)
    {
        // TODO: Implement setStoreName() method.
    }

    public function setXForwardedFor($xForwardedFor)
    {
        // TODO: Implement setXForwardedFor() method.
    }

    public function setCustomerNote($customerNote)
    {
        // TODO: Implement setCustomerNote() method.
    }

    public function setUpdatedAt($timestamp)
    {
        // TODO: Implement setUpdatedAt() method.
    }

    public function setTotalItemCount($totalItemCount)
    {
        // TODO: Implement setTotalItemCount() method.
    }

    public function setCustomerGender($customerGender)
    {
        // TODO: Implement setCustomerGender() method.
    }

    public function setDiscountTaxCompensationAmount($amount)
    {
        // TODO: Implement setDiscountTaxCompensationAmount() method.
    }

    public function setBaseDiscountTaxCompensationAmount($amount)
    {
        // TODO: Implement setBaseDiscountTaxCompensationAmount() method.
    }

    public function setShippingDiscountTaxCompensationAmount($amount)
    {
        // TODO: Implement setShippingDiscountTaxCompensationAmount() method.
    }

    public function setBaseShippingDiscountTaxCompensationAmnt($amnt)
    {
        // TODO: Implement setBaseShippingDiscountTaxCompensationAmnt() method.
    }

    public function setDiscountTaxCompensationInvoiced($discountTaxCompensationInvoiced)
    {
        // TODO: Implement setDiscountTaxCompensationInvoiced() method.
    }

    public function setBaseDiscountTaxCompensationInvoiced($baseDiscountTaxCompensationInvoiced)
    {
        // TODO: Implement setBaseDiscountTaxCompensationInvoiced() method.
    }

    public function setDiscountTaxCompensationRefunded($discountTaxCompensationRefunded)
    {
        // TODO: Implement setDiscountTaxCompensationRefunded() method.
    }

    public function setBaseDiscountTaxCompensationRefunded($baseDiscountTaxCompensationRefunded)
    {
        // TODO: Implement setBaseDiscountTaxCompensationRefunded() method.
    }

    public function setShippingInclTax($amount)
    {
        // TODO: Implement setShippingInclTax() method.
    }

    public function setBaseShippingInclTax($amount)
    {
        // TODO: Implement setBaseShippingInclTax() method.
    }

    public function getExtensionAttributes()
    {
        // TODO: Implement getExtensionAttributes() method.
    }

    public function setExtensionAttributes(\Magento\Sales\Api\Data\OrderExtensionInterface  $extensionAttributes)
    {
        // TODO: Implement setExtensionAttributes() method.
    }
}
