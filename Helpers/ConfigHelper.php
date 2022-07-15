<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Entities\Merchant;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class ConfigHelper extends AbstractHelper
{
    const XML_PATH_PAYMENT = 'payment';
    const XML_PATH_METHODE = Config::CODE;
    const XML_PATH_CANLOG = 'duration';
    const CONFIG_DEBUG = 'debug';
    const TRIGGER_IS_ALLOWED = 'trigger_is_allowed';
    const TRIGGER_IS_ENABLED = 'trigger_is_enabled';
    const TRIGGER_TYPOLOGY = 'trigger_typology';
    const PAYMENT_EXPIRATION_TIME = 'payment_expiration';

    /**
     * @var WriterInterface
     */
    private $writerInterface;

    public function __construct(
        Context $context,
        WriterInterface $writerInterface
    ) {
        parent::__construct($context);
        $this->writerInterface = $writerInterface;
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
        return $this->getConfigValue(self::XML_PATH_PAYMENT . '/' . self::XML_PATH_METHODE . '/' . $code, $storeId);
    }

    private function getConfigValue($code, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $code,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @return bool
     */
    public function triggerIsEnabled(): bool
    {
        return ($this->getConfigByCode(self::TRIGGER_IS_ALLOWED) && $this->getConfigByCode(self::TRIGGER_IS_ENABLED));
    }

    /**
     * @return string
     */
    public function getTrigger(): string
    {
        return $this->getConfigByCode(self::TRIGGER_TYPOLOGY);
    }

    /**
     * @return string
     */
    public function getTranslatedTrigger(): string
    {
        return __($this->getTrigger());
    }

    /**
     * @return int
     */
    public function getPaymentExpirationTime(): int
    {
        return (int)$this->getConfigByCode(self::PAYMENT_EXPIRATION_TIME);
    }

    /**
     * @param $path
     * @param $value
     *
     * @return void
     */
    public function saveConfig($path, $value, $scope, $storeId): void
    {
        $this->writerInterface->save(self::XML_PATH_PAYMENT . '/' . self::XML_PATH_METHODE . '/' . $path, $value, $scope, $storeId);
    }

    /**
     * @param string $path
     * @param Merchant|bool $merchant
     *
     * @return void
     */
    public function saveMerchantId(string $path, $merchant, $scope, $storeId): void
    {
        if ($merchant) {
            $this->saveConfig($path, $merchant->id, $scope, $storeId);
        }
    }
}
