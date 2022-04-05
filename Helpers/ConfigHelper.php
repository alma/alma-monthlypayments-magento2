<?php
namespace Alma\MonthlyPayments\Helpers;

use Magento\Store\Model\ScopeInterface;
use Alma\MonthlyPayments\Gateway\Config\Config;

class ConfigHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_PAYMENT = 'payment';
    const XML_PATH_METHODE = Config::CODE;
    const XML_PATH_CANLOG = 'duration';
    const CONFIG_DEBUG = 'debug';
    const TRIGGER_IS_ALLOWED = 'trigger_is_allowed';
    const TRIGGER_IS_ENABLED = 'trigger_is_enabled';
    const TRIGGER_TYPOLOGY = 'trigger_typology';

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
}
