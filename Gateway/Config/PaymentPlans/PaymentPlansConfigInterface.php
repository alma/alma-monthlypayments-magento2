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


use Alma\API\RequestError;

interface PaymentPlansConfigInterface
{
    /**
     * Update instance's default data with fresh fee plans fetched from Alma API
     *
     */
    public function updateFromApi();

    /**
     *  Merge in default config data for given plan
     */
    public function updatePlanDefaults(string $planKey, array $defaultConfig);

    /**
     *  Update given plan's allowed state – use this instead of updatePlanDefaults for `allowed`, as it's not about
     *  being a default but really to override any existing value with a fresher one from the API
     */
    public function setPlanAllowed(string $planKey, bool $allowed);

    /**
     * Get configured/available plans (allowed or not)
     *
     * @return PaymentPlanConfigInterface[]
     */
    public function getPlans(): array;

    /**
     * Get plans that are allowed & enabled by the merchant
     *
     * @return PaymentPlanConfigInterface[]
     */
    public function getEnabledPlans(): array;

    /**
     * Get a JSON representation of the plans configuration
     */
    public function toJson(): string;
}
