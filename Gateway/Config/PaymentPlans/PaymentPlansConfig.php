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

namespace Alma\MonthlyPayments\Gateway\Config\PaymentPlans;

use Alma\API\Entities\FeePlan;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Magento\Framework\Serialize\Serializer\Json;
use Alma\MonthlyPayments\Helpers\Logger;

class PaymentPlansConfig implements PaymentPlansConfigInterface
{
    /** @var array */
    private $data;
    /**
     * @var Json
     */
    private $serializer;
    /**
     * @var AlmaClient
     */
    private $almaClient;
    /**
     * @var PaymentPlanConfigInterfaceFactory
     */
    private $planConfigFactory;
    /**
     * @var Logger
     */
    private  $logger;

    /**
     * PaymentPlansConfig constructor.
     *
     * @param AlmaClient $almaClient
     * @param PaymentPlanConfigInterfaceFactory $planConfigFactory
     * @param array|string $data
     * @param Logger $logger
     */
    public function __construct(
        AlmaClient $almaClient,
        PaymentPlanConfigInterfaceFactory $planConfigFactory,
        Logger $logger,
        $data = []
    )
    {
        $this->serializer = new Json();

        if (is_string($data)) {
            $data = $this->serializer->unserialize($data);
        }

        $this->data = $data;
        $this->almaClient = $almaClient;
        $this->planConfigFactory = $planConfigFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function updateFromApi()
    {
        $alma = $this->almaClient->getDefaultClient();
        if (!$alma) {
            return;
        }

        $feePlans = $alma->merchants->feePlans(FeePlan::KIND_GENERAL, "all", true);
        foreach ($feePlans as $plan) {
            $key = PaymentPlanConfig::keyForFeePlan($plan);
            $this->setPlanAllowed($key, $plan->allowed);
            $this->updatePlanDefaults($key, PaymentPlanConfig::defaultConfigForFeePlan($plan));
        }
    }

    /**
     * @inheritDoc
     */
    public function updatePlanDefaults(string $planKey, array $defaultConfig)
    {
        $currentConfig = key_exists($planKey, $this->data) ? $this->data[$planKey] : [];
        $this->data[$planKey] = array_merge($defaultConfig, $currentConfig);
    }

    /**
     * @inheritDoc
     */
    public function setPlanAllowed(string $planKey, bool $allowed)
    {
        if (!key_exists($planKey, $this->data)) {
            $this->data[$planKey] = [];
        }

        $this->data[$planKey]['allowed'] = $allowed;
    }

    /**
     * @inheritDoc
     */
    public function getPlans(): array
    {
        return array_map(function ($planData) {
            return $this->planConfigFactory->create(["data" => $planData]);
        }, $this->data);
    }

    /**
     * @inheritDoc
     */
    public function getEnabledPlans(): array
    {
        return array_filter($this->getPlans(), function ($planConfig) {
            return $planConfig->isAllowed() && $planConfig->isEnabled();
        });
    }

    /**
     * @inheritDoc
     */
    public function toJson(): string
    {
        return $this->serializer->serialize($this->data);
    }
}
