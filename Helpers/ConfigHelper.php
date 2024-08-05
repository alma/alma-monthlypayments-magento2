<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Entities\FeePlan;
use Alma\API\Entities\Merchant;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Magento\Framework\App\Cache\Type\Config as CacheConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\SerializerInterface;

class ConfigHelper extends AbstractHelper
{
    const XML_PATH_PAYMENT = 'payment';
    const XML_PATH_METHODE = Config::CODE;
    const TRIGGER_IS_ALLOWED = 'trigger_is_allowed';
    const TRIGGER_IS_ENABLED = 'trigger_is_enabled';
    const TRIGGER_TYPOLOGY = 'trigger_typology';
    const IN_PAGE_ENABLED = 'in_page_enabled';
    const PAYMENT_EXPIRATION_TIME = 'payment_expiration';
    const BASE_PLANS_CONFIG = 'base_config_plans';
    /**
     * @var WriterInterface
     */
    private $writerInterface;
    /**
     * @var StoreHelper
     */
    private $storeHelper;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var TypeListInterface
     */
    private $typeList;

    /**
     * @param Context $context
     * @param StoreHelper $storeHelper
     * @param WriterInterface $writerInterface
     * @param SerializerInterface $serializer
     * @param TypeListInterface $typeList
     */
    public function __construct(
        Context $context,
        StoreHelper $storeHelper,
        WriterInterface $writerInterface,
        SerializerInterface $serializer,
        TypeListInterface $typeList
    ) {
        parent::__construct($context);
        $this->writerInterface = $writerInterface;
        $this->storeHelper = $storeHelper;
        $this->serializer = $serializer;
        $this->typeList = $typeList;
    }

    /**
     * @param $code
     * @param string|null $scope
     * @param string|null $storeId
     *
     * @return string|null
     */
    public function getConfigByCode($code, ?string $scope = null, ?string $storeId = null): ?string
    {
        if (!$storeId) {
            $storeId = $this->storeHelper->getStoreId();
        }
        if (!$scope) {
            $scope = $this->storeHelper->getScope();
        }

        return $this->getConfigValue($this->getConfigPath($code), $scope, $storeId);
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
     * Get in page activation setting
     *
     * @return bool
     */
    public function isInPageEnabled(): bool
    {
        return (bool)$this->getConfigByCode(self::IN_PAGE_ENABLED);
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
            $this->cleanCache(CacheConfig::TYPE_IDENTIFIER);
        }
    }

    /**
     * Disable in page
     *
     * @return void
     */
    public function disableInPage():void
    {
        $this->saveConfig(self::IN_PAGE_ENABLED, 0, $this->storeHelper->getScope(), $this->storeHelper->getStoreId());
    }

    /**
     * @param string $path
     * @param $scope
     * @param $storeId
     *
     * @return void
     */
    public function deleteConfig(string $path, $scope, $storeId): void
    {
        $this->writerInterface->delete($this->getConfigPath($path), $scope, $storeId);
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
        $this->cleanCache(CacheConfig::TYPE_IDENTIFIER);
    }

    /**
     * @param $configCode
     *
     * @return string
     */
    protected function getConfigPath($configCode): string
    {
        return self::XML_PATH_PAYMENT . '/' . self::XML_PATH_METHODE . '/' . $configCode;
    }

    /**
     * @param array $plans
     *
     * @return void
     */
    public function saveBasePlansConfig(array $plans): void
    {
        $this->saveConfig(self::BASE_PLANS_CONFIG, $this->serializer->serialize($plans), $this->storeHelper->getScope(), $this->storeHelper->getStoreId());
        $this->cleanCache(CacheConfig::TYPE_IDENTIFIER);
    }

    public function saveIsAllowedInsurance($merchant, $scope, $storeId):void
    {
        $isAllowedInsurance = 0;
        if ($merchant) {
            $isAllowedInsurance = 1;
            if (isset($merchant->cms_insurance)) {
                $isAllowedInsurance = $merchant->cms_insurance ? 1 : 0;
            }
        }
        $this->saveIsAllowedInsuranceValue($isAllowedInsurance, $scope, $storeId);
    }

    public function saveIsAllowedInsuranceValue($value, $scope, $storeId):void
    {
        $this->saveConfig(InsuranceHelper::IS_ALLOWED_INSURANCE_PATH, $value, $scope, $storeId);
    }
    public function clearInsuranceConfig($scope, $storeId):void
    {
        $this->saveConfig(InsuranceHelper::ALMA_INSURANCE_CONFIG_CODE, null, $scope, $storeId);
    }

    /**
     * @return FeePlan[]
     */
    public function getBaseApiPlansConfig(): array
    {
        $baseApiFeePlansInArray = $this->serializer->unserialize($this->getConfigByCode(self::BASE_PLANS_CONFIG));
        $feePlans = [];
        foreach ($baseApiFeePlansInArray as $key => $feePlanInArray) {
            $feePlans[$key] = new FeePlan($feePlanInArray);
        }
        return $feePlans;
    }

    /**
     * @param string $type
     *
     * @return void
     */
    private function cleanCache(string $type): void
    {
        $this->typeList->cleanType($type);
    }
}
