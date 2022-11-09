<?php

namespace Alma\MonthlyPayments\Model\Adminhtml\Config;

use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;

class FeePlansConfigBackendModel extends Value
{
    /**
     * @var SerializerInterface
     */
    private $serialize;
    /**
     * @var PaymentPlansHelper
     */
    private $paymentPlansHelper;
    /**
     * @var ConfigHelper
     */
    private $configHelper;


    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        SerializerInterface $serialize,
        PaymentPlansHelper $paymentPlansHelper,
        ConfigHelper $configHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
        $this->serialize = $serialize;
        $this->paymentPlansHelper = $paymentPlansHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * @return void
     */
    public function beforeSave(): void
    {

        $value = $this->getValue();
        if (isset($value['__empty'])) {
            unset($value['__empty']);
        }
        $almaPlans = $this->configHelper->getBaseApiPlansConfig();
        $feePlans = [];
        foreach ($almaPlans as $key => $feePlan) {
            if (!$this->isAllowedPlan($key, $feePlan)) {
                continue;
            }
            $feePlans[$key] = $this->paymentPlansHelper->formatFeePlanConfigForSave($feePlan, $value[$key] ?? []);
        }
        $encodedValue = $this->serialize->serialize($feePlans);

        $this->setValue($encodedValue);
    }

    /**
     * @return void
     */
    public function _afterLoad(): void
    {
        $value = $this->getValue();
        $almaPlans = $this->configHelper->getBaseApiPlansConfig();
        if ($value) {
            $value = $this->serialize->unserialize($value);
        }
        $feePlans = [];
        foreach ($almaPlans as $key => $feePlan) {
            if (!$this->isAllowedPlan($key, $feePlan)) {
                continue;
            }
            $feePlans[$key] = $this->paymentPlansHelper->formatLocalFeePlanConfig($feePlan, $value[$key] ?? []);
        }

        $this->setValue($feePlans);
    }

    /**
     *
     *
     * @param $key
     * @param $feePlan
     * @return bool
     */
    public function isAllowedPlan($key, $feePlan): bool
    {
        if ($key !== 'general:1:0:0' && $feePlan->allowed) {
            return true;
        }
        return false;
    }
}
