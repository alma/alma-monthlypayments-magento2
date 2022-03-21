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

class PaymentPlans extends Serialized
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
            $plansConfig->updateFromApi();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __("Error fetching Alma payment plans - displayed information might not be accurate")
            );

            return;
        }

        $this->setValue($plansConfig);
    }

    public function beforeSave()
    {
        $value = $this->getValue();

        if (!is_array($value)) {
            $value = $this->serializer->unserialize($value);
        }
        $value = $this->forceMinMaxLimit($value);
        // Remove transient values from the serialized data: it should always come fresh from the API
        foreach (PaymentPlanConfig::transientKeys() as $key) {
            foreach ($value as $planKey => &$planConfig) {
                unset($planConfig[$key]);
            }
        }

        // Parent class will serialize the value as JSON again in its beforeSave implementation
        $this->setValue($value);

        return parent::beforeSave();
    }

    /**
     * Check if min and max value are in Alma limit for each plan configuration before serialize it in core_config database
     *
     * @param array $plans
     * @return array
     */
    private function forceMinMaxLimit(array $plans):array
    {
        foreach ($plans as &$plan) {
            if($plan[PaymentPlanConfig::KEY_MIN_AMOUNT]<$plan[PaymentPlanConfig::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT] || $plan[PaymentPlanConfig::KEY_MIN_AMOUNT]>$plan[PaymentPlanConfig::KEY_MAX_AMOUNT]){
                $this->logger->info('Change min Amount: '.$plan[PaymentPlanConfig::KEY_MAX_AMOUNT].' - Min allowed Amount: '.$plan[PaymentPlanConfig::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT]. '- Max Amount: '.$plan[PaymentPlanConfig::KEY_MAX_AMOUNT],[]);
                $this->messageManager->addErrorMessage(
                    sprintf(__("Minimum amount is %s€ for plan %s"),($plan[PaymentPlanConfig::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT]/100),$plan["key"])
                );
                $plan[PaymentPlanConfig::KEY_MIN_AMOUNT] = $plan[PaymentPlanConfig::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT];
            }
            if($plan[PaymentPlanConfig::KEY_MAX_AMOUNT]>$plan[PaymentPlanConfig::TRANSIENT_KEY_MAX_ALLOWED_AMOUNT] || $plan[PaymentPlanConfig::KEY_MAX_AMOUNT]<$plan[PaymentPlanConfig::KEY_MIN_AMOUNT]){
                $this->logger->info('Change max Amount: '.$plan[PaymentPlanConfig::KEY_MAX_AMOUNT].' - Max allowed Amount: '.$plan[PaymentPlanConfig::TRANSIENT_KEY_MAX_ALLOWED_AMOUNT]. '- Min Amount: '.$plan[PaymentPlanConfig::KEY_MIN_AMOUNT],[]);
                $this->messageManager->addErrorMessage(
                    sprintf(__("Maximum amount is %s€ for plan %s"),($plan[PaymentPlanConfig::TRANSIENT_KEY_MAX_ALLOWED_AMOUNT]/100),$plan["key"])
                );
                $plan[PaymentPlanConfig::KEY_MAX_AMOUNT] = $plan[PaymentPlanConfig::TRANSIENT_KEY_MAX_ALLOWED_AMOUNT];
            }
        }
        return $plans;
    }
}
