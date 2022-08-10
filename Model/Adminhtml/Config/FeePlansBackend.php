<?php

/**
 * 2018-2021 Alma SAS
 *
 * THE MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
 * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @author    Alma SAS <contact@getalma.eu>
 * @copyright 2018-2021 Alma SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace Alma\MonthlyPayments\Model\Adminhtml\Config;

use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlanConfig;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterfaceFactory;
use Magento\Config\Model\Config\Backend\Serialized;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\Manager as MessageManager;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Alma\MonthlyPayments\Helpers\Logger;

class FeePlansBackend extends Serialized
{
    protected $apiKeyType = null;

    /**
     * @var MessageManager
     */
    private $messageManager;
    /**
     * @var false
     */
    protected $hasError;
    /**
     * @var Json
     */
    protected $serializer;
    /**
     * @var PaymentPlansConfigInterfaceFactory
     */
    private $plansConfigFactory;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param MessageManager $messageManager
     * @param PaymentPlansConfigInterfaceFactory $plansConfigFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param Json|null $serializer
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        MessageManager $messageManager,
        PaymentPlansConfigInterfaceFactory $plansConfigFactory,
        Logger $logger,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        Json $serializer = null
    )
    {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data,
            $serializer
        );

        $this->messageManager = $messageManager;
        $this->serializer = $serializer ?: new Json();
        $this->plansConfigFactory = $plansConfigFactory;
        $this->logger = $logger;
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();

        $value = $this->getValue();
        if ($value === false) {
            $value = [];
        }

        $plansConfig = $this->plansConfigFactory->create(["data" => $value]);

        try {
            $this->logger->info('MAJ API DESACTIVE', []);
            $plansConfig->updateFromApi();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __("Error fetching Alma payment plans - displayed information might not be accurate")
            );

            return;
        }

        $this->setValue($plansConfig);
    }

    /**
     * @return FeePlansBackend
     */
    public function beforeSave()
    {
        $paymentPlans = $this->getValue();

        if (!is_array($paymentPlans)) {
            $paymentPlans = $this->serializer->unserialize($paymentPlans);
        }
        foreach ($paymentPlans as &$plan) {
            $this->initAllowedAmountThresholds($plan);
            $this->forceAmountThresholds($plan);
            $this->cleanTransientKeys($plan);
        }

        // Parent class will serialize the value as JSON again in its beforeSave implementation
        $this->setValue($paymentPlans);

        return parent::beforeSave();
    }

    /**
     * Remove transient values from the serialized data: it should always come fresh from the API
     * @param array $plan
     *
     * @return void
     */
    private function cleanTransientKeys(array &$plan)
    {
        foreach (PaymentPlanConfig::transientKeys() as $key) {
            unset($plan[$key]);
        }
    }

    /**
     * Check if min and max value are in Alma limit for the plan configuration before serialize it in core_config database
     *
     * @param array $plan
     *
     * @return void
     */
    private function forceAmountThresholds(array &$plan): void
    {
        if ($plan[PaymentPlanConfig::KEY_MIN_AMOUNT] < $plan[PaymentPlanConfig::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT] || $plan[PaymentPlanConfig::KEY_MIN_AMOUNT] > $plan[PaymentPlanConfig::KEY_MAX_AMOUNT]) {
            $this->messageManager->addErrorMessage(
                sprintf(__("Minimum amount is %s€ for plan %s"), ($plan[PaymentPlanConfig::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT] / 100), $plan["key"])
            );
            $plan[PaymentPlanConfig::KEY_MIN_AMOUNT] = $plan[PaymentPlanConfig::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT];
        }
        if ($plan[PaymentPlanConfig::KEY_MAX_AMOUNT] > $plan[PaymentPlanConfig::TRANSIENT_KEY_MAX_ALLOWED_AMOUNT] || $plan[PaymentPlanConfig::KEY_MAX_AMOUNT] < $plan[PaymentPlanConfig::KEY_MIN_AMOUNT]) {
            $this->messageManager->addErrorMessage(
                sprintf(__("Maximum amount is %s€ for plan %s"), ($plan[PaymentPlanConfig::TRANSIENT_KEY_MAX_ALLOWED_AMOUNT] / 100), $plan["key"])
            );
            $plan[PaymentPlanConfig::KEY_MAX_AMOUNT] = $plan[PaymentPlanConfig::TRANSIENT_KEY_MAX_ALLOWED_AMOUNT];
        }
    }

    /**
     * @param array $plan
     *
     * @return bool
     */
    private function hasAllowedAmountThresholds(array $plan): bool
    {
        return isset($plan[PaymentPlanConfig::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT]) && isset($plan[PaymentPlanConfig::TRANSIENT_KEY_MAX_ALLOWED_AMOUNT]);
    }

    /**
     * @param array $plan
     *
     * @return void
     */
    private function initAllowedAmountThresholds(array &$plan)
    {
        if (!$this->hasAllowedAmountThresholds($plan)) {
            $plan[PaymentPlanConfig::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT] = $plan[PaymentPlanConfig::KEY_MIN_AMOUNT];
            $plan[PaymentPlanConfig::TRANSIENT_KEY_MAX_ALLOWED_AMOUNT] = $plan[PaymentPlanConfig::KEY_MAX_AMOUNT];
        }
    }
}
