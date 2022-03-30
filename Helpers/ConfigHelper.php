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
}
