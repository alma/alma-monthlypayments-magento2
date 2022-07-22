<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Entities\Merchant;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreResolver;

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

    /**
     * @var StoreResolver
     */
    private $storeResolver;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var State
     */
    private $state;

    public function __construct(
        StoreResolver $storeResolver,
        Context $context,
        RequestInterface $request,
        State $state,
        WriterInterface $writerInterface
    ) {
        parent::__construct($context);
        $this->writerInterface = $writerInterface;
        $this->storeResolver = $storeResolver;
        $this->request = $request;
        $this->state = $state;
    }

    /**
     * @return bool
     */
    public function canLog(): bool
    {
        return (bool)(int)$this->getConfigByCode(self::CONFIG_DEBUG);
    }

    /**
     * @param $code
     * @param $scope
     * @param $storeId
     *
     * @return string
     */
    public function getConfigByCode($code, $scope = null, $storeId = null): string
    {

        $store = $this->request->getParam('store');
        $website = $this->request->getParam('website');
        /**
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/alma.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info('$store');
        $logger->info($store);
        $logger->info('$website');
        $logger->info($website);
        $logger->info($this->state->getAreaCode());
        */
        if ($store) {
            $id = $store;
            $type = ScopeInterface::SCOPE_STORES;
        } elseif ($website) {
            $id = $website;
            $type = ScopeInterface::SCOPE_WEBSITES;
        } else {
            $id = $this->storeResolver->getCurrentStoreId();
            $type = ScopeInterface::SCOPE_STORES;
        }
        if ($storeId) {
            $id = $storeId;
        }
        if ($scope) {
            $type = $scope;
        }


        return $this->getConfigValue($this->getConfigPath($code), $type, $id);
    }

    /**
     * @param string $code
     * @param string $scope
     * @param null|int|string $storeId
     *
     * @return mixed
     */
    private function getConfigValue(string $code, string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $code,
            $scope,
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
     * @param $scope
     * @param $storeId
     *
     * @return void
     */
    public function saveConfig($path, $value, $scope, $storeId): void
    {
        $this->writerInterface->save($this->getConfigPath($path), $value, $scope, $storeId);
    }

    /**
     * @param string $path
     * @param Merchant|bool $merchant
     * @param $scope
     * @param $storeId
     *
     * @return void
     */
    public function saveMerchantId(string $path, $merchant, $scope, $storeId): void
    {
        if ($merchant) {
            $this->saveConfig($path, $merchant->id, $scope, $storeId);
        }
    }

    /**
     * @param $scope
     * @param $storeId
     *
     * @return void
     */
    public function changeApiModeToTest($scope, $storeId): void
    {
        $this->writerInterface->delete($this->getConfigPath('api_mode'), $scope, $storeId);
    }

    /**
     * @param $storeId
     *
     * @return string
     */
    protected function getScope($storeId): string
    {
        if ($storeId) {
            $scope = ScopeInterface::SCOPE_STORES;
        }
        return $scope;
    }

    protected function getConfigPath($configCode): string
    {
        return self::XML_PATH_PAYMENT . '/' . self::XML_PATH_METHODE . '/' . $configCode;
    }

}
