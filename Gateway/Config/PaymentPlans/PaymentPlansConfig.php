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

use Magento\Framework\Serialize\Serializer\Json;

class PaymentPlansConfig
{
    /** @var array */
    private $data;
    private $serializer;

    /**
     * PaymentPlansConfig constructor.
     * @param array|string $data
     */
    public function __construct($data)
    {
        $this->serializer = new Json();

        if (is_string($data)) {
            $data = $this->serializer->unserialize($data);
        }

        $this->data = $data;
    }

    public function updatePlanDefaults(string $planKey, array $defaultConfig)
    {
        $currentConfig = key_exists($planKey, $this->data) ? $this->data[$planKey] : [];
        $this->data[$planKey] = array_merge($defaultConfig, $currentConfig);
    }

    public function setPlanAllowed(string $planKey, bool $allowed)
    {
        if (!key_exists($planKey, $this->data)) {
            $this->data[$planKey] = [];
        }

        $this->data[$planKey]['allowed'] = $allowed;
    }

    /**
     * @return PaymentPlanConfig[]
     */
    public function getPlans(): array
    {
        return array_map(function ($planData) {
            return new PaymentPlanConfig($planData);
        }, $this->data);
    }

    /**
     * @return PaymentPlanConfig[]
     */
    public function getEnabledPlans(): array
    {
        return array_filter($this->getPlans(), function ($planConfig) {
            return $planConfig->isAllowed() && $planConfig->isEnabled();
        });
    }

    public function toJson(): string
    {
        return $this->serializer->serialize($this->data);
    }
}
