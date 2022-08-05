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


namespace Alma\MonthlyPayments\Helpers;

use Alma\API\RequestError;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterface;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterfaceFactory;
use Magento\Framework\Message\Manager as MessageManager;

class PaymentPlansHelper
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var MessageManager
     */
    private $messageManager;
    private $plansConfigFactory;
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @param Logger $logger
     * @param PaymentPlansConfigInterfaceFactory $configInterfaceFactory
     * @param MessageManager $messageManager
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Logger $logger,
        PaymentPlansConfigInterfaceFactory $configInterfaceFactory,
        MessageManager $messageManager,
        ConfigHelper $configHelper
    ) {
        $this->logger = $logger;
        $this->plansConfigFactory = $configInterfaceFactory;
        $this->messageManager = $messageManager;
        $this->configHelper = $configHelper;
    }

    /**
     * @return bool
     */
    public function paymentTriggerIsAllowed(): bool
    {
        $triggerIsAllowed = false;

        try {
            $plansConfig = $this->updatePlanConfigFromApi();
        } catch (RequestError $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            return false;
        }

        foreach ($plansConfig->getPlans() as $plan) {
            if ($plan->hasDeferredTrigger()) {
                $triggerIsAllowed = true;
                break;
            }
        }
        return $triggerIsAllowed;
    }

    /**
     * @param array $value
     * @return PaymentPlansConfigInterface
     */
    public function createPlanConfig(array $value = []): PaymentPlansConfigInterface
    {
        return $this->plansConfigFactory->create(["data" => $value]);
    }

    /**
     * @return PaymentPlansConfigInterface
     * @throws RequestError
     */
    public function updatePlanConfigFromApi(): PaymentPlansConfigInterface
    {
        $plansConfig = $this->createPlanConfig();
        try {
            $plansConfig->updateFromApi();
        } catch (RequestError $e) {
            $this->logger->error('Error fetching Alma payment plans : ', [$e->getMessage()]);
            throw new RequestError("Error fetching Alma payment plans - displayed information might not be accurate");
        }
        return $plansConfig;
    }

    public function saveBaseApiPlansConfig(): void
    {
        try {
            $apiPlans = $this->updatePlanConfigFromApi()->getPlans();
            $plans = [];
            foreach ($apiPlans as $planKey => $apiPlan) {
                $plans[$planKey] = $apiPlan->toArray();
            }
            $this->configHelper->saveBasePlansConfig($plans);
        } catch (RequestError $e) {
            $this->logger->error('Error in save api base config plans', [$e->getMessage()]);
        }
    }
}
