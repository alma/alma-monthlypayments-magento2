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
}
