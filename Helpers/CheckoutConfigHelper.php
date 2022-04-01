<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\MonthlyPayments\Gateway\Config\Config;

class CheckoutConfigHelper extends Config
{
    const MERGE_PAYMENT_TITLE = 'title';
    const MERGE_PAYMENT_DESC = 'description';
    const INSTALLMENTS_PAYMENT_TITLE = 'alma_installments_payment_title';
    const INSTALLMENTS_PAYMENT_DESC = 'alma_installments_payment_desc';
    const SPREAD_PAYMENT_TITLE = 'alma_spread_payment_title';
    const SPREAD_PAYMENT_DESC = 'alma_spread_payment_desc';
    const DEFERRED_PAYMENT_TITLE = 'alma_deferred_payment_title';
    const DEFERRED_PAYMENT_DESC = 'alma_deferred_payment_desc';
    const MERGE_PAYEMENT_METHODS = 'alma_merge_payment';

    /**
     * Get payment installment title
     * @return string
     */
    public function getInstallmentsPaymentTitle(): string
    {
        return (string)$this->get(self::INSTALLMENTS_PAYMENT_TITLE);
    }

    /**
     * Get payment installment description
     * @return string
     */
    public function getInstallmentsPaymentDesc(): string
    {
        return (string)$this->get(self::INSTALLMENTS_PAYMENT_DESC);
    }

    /**
     * Get payment spread title
     * @return string
     */
    public function getSpreadPaymentTitle(): string
    {
        return (string)$this->get(self::SPREAD_PAYMENT_TITLE);
    }
    /**
     * Get payment spread description
     * @return string
     */
    public function getSpreadPaymentDesc(): string
    {
        return (string)$this->get(self::SPREAD_PAYMENT_DESC);
    }

    /**
     * Get deferred payment title
     * @return string
     */
    public function getDeferredPaymentTitle(): string
    {
        return (string)$this->get(self::DEFERRED_PAYMENT_TITLE);
    }
    /**
     * Get deferred payment description
     * @return string
     */
    public function getDeferredPaymentDesc(): string
    {
        return (string)$this->get(self::DEFERRED_PAYMENT_DESC);
    }

    /**
     * Get merge payment title
     * @return string
     */
    public function getMergePaymentTitle(): string
    {
        return (string)$this->get(self::MERGE_PAYMENT_TITLE);
    }
    /**
     * Get merge payment description
     * @return string
     */
    public function getMergePaymentDesc(): string
    {
        return (string)$this->get(self::MERGE_PAYMENT_DESC);
    }
    /**
     * Get merge payment config flag
     * @return int
     */
    public function getAreMergedPaymentMethods():int
    {
        return (bool)(int)$this->get(self::MERGE_PAYEMENT_METHODS);
    }
}
