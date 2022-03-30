<?php
namespace Alma\MonthlyPayments\Helpers;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Magento\Framework\App\Config\Storage\WriterInterface;

class ConfigHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_PAYMENT = 'payment';
    const XML_PATH_METHODE = Config::CODE;
    const XML_PATH_CANLOG = 'duration';
    const CONFIG_DEBUG = 'debug';
    const MERGE_PAYEMENT_METHODS = 'alma_merge_payment';
    const INSTALLMENTS_PAYMENT_TITLE = 'alma_installments_payment_title';
    const INSTALLMENTS_PAYMENT_DESC = 'alma_installments_payment_desc';
    const SPREAD_PAYMENT_TITLE = 'alma_spread_payment_title';
    const SPREAD_PAYMENT_DESC = 'alma_spread_payment_desc';
    const DEFERRED_PAYMENT_TITLE = 'alma_deferred_payment_title';
    const DEFERRED_PAYMENT_DESC = 'alma_deferred_payment_desc';
    const MERGE_PAYMENT_TITLE = 'title';
    const MERGE_PAYMENT_DESC = 'description';
    const TRIGGER_IS_ALLOWED = 'trigger_is_allowed';
    const TRIGGER_IS_ENABLED = 'trigger_is_enabled';
    const TRIGGER_TYPOLOGY = 'trigger_typology';
    const SHARE_CHECKOUT_ENABLE_KEY = 'share_checkout_enable';
    const SHARE_CHECKOUT_DATE_KEY = 'share_checkout_date';

    /**
     * @var WriterInterface
     */
    private $writer;


    public function __construct(
        Context $context,
        WriterInterface $writer
    )
    {
        parent::__construct($context);
        $this->writer = $writer;
    }


    /**
     * @return bool
     */
    public function canLog(): bool
    {
        return (bool)(int)$this->getConfigByCode(self::CONFIG_DEBUG);
    }

    public function getConfigByCode($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_PAYMENT.'/'.self::XML_PATH_METHODE.'/'.$code, $storeId);
    }

    private function getConfigValue($code, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $code, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * @return bool
     */
    public function triggerIsEnabled():bool
    {
        return ($this->getConfigByCode(self::TRIGGER_IS_ALLOWED) && $this->getConfigByCode(self::TRIGGER_IS_ENABLED));
    }

    /**
     * @return string
     */
    public function getTrigger():string
    {
        return $this->getConfigByCode(self::TRIGGER_TYPOLOGY);
    }

    /**
     * @return string
     */
    public function getTranslatedTrigger():string
    {
        return __($this->getTrigger());
    }

    /**
     * Get merge payment config flag
     * @return int
     */
    public function getAreMergedPaymentMethods()
    {
        return (bool)(int)$this->getConfigByCode(self::MERGE_PAYEMENT_METHODS);
    }
    /**
     * Get payment installment title
     * @return string
     */
    public function getInstallmentsPaymentTitle()
    {
        return (string)$this->getConfigByCode(self::INSTALLMENTS_PAYMENT_TITLE);
    }

    /**
     * Get payment installment description
     * @return string
     */
    public function getInstallmentsPaymentDesc()
    {
        return (string)$this->getConfigByCode(self::INSTALLMENTS_PAYMENT_DESC);
    }

    /**
     * Get payment spread title
     * @return string
     */
    public function getSpreadPaymentTitle()
    {
        return (string)$this->getConfigByCode(self::SPREAD_PAYMENT_TITLE);
    }
    /**
     * Get payment spread description
     * @return string
     */
    public function getSpreadPaymentDesc()
    {
        return (string)$this->getConfigByCode(self::SPREAD_PAYMENT_DESC);
    }

    /**
     * Get deferred payment title
     * @return string
     */
    public function getDeferredPaymentTitle()
    {
        return (string)$this->getConfigByCode(self::DEFERRED_PAYMENT_TITLE);
    }
    /**
     * Get deferred payment description
     * @return string
     */
    public function getDeferredPaymentDesc()
    {
        return (string)$this->getConfigByCode(self::DEFERRED_PAYMENT_DESC);
    }

    /**
     * Get merge payment title
     * @return string
     */
    public function getMergePaymentTitle()
    {
        return (string)$this->getConfigByCode(self::MERGE_PAYMENT_TITLE);
    }
    /**
     * Get merge payment description
     * @return string
     */
    public function getMergePaymentDesc()
    {
        return (string)$this->getConfigByCode(self::MERGE_PAYMENT_DESC);
    }
    /**
     * Get merge payment description
     * @return string
     */
    public function shareOfCheckoutIsEnabled()
    {
        return (string)$this->getConfigByCode(self::SHARE_CHECKOUT_ENABLE_KEY);
    }

    /**
     * @param $date
     * @return void
     */
    public function saveShareOfCheckoutDate($date):void
    {
        try {
            $this->writer->save(self::XML_PATH_PAYMENT . '/' . self::XML_PATH_METHODE . '/' . self::SHARE_CHECKOUT_DATE_KEY, $date);
        } catch (\Exception $e) {

        }
    }
    /**
     * @return void
     */
    public function deleteShareOfCheckoutDate():void
    {
        try {
            $this->writer->delete(self::XML_PATH_PAYMENT . '/' . self::XML_PATH_METHODE . '/' . self::SHARE_CHECKOUT_DATE_KEY);
        } catch (\Exception $e) {

        }
    }
}
