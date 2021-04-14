<?php
/**
 * 2018-2020 Alma SAS
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
 * @copyright 2018-2020 Alma SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace Alma\MonthlyPayments\Api\Data;

interface EligiblePlansResultInterface {
    /**
     * Return the plan's ID key
     *
     * @api
     * @return string
     */
    public function getKey();

    /**
     * Return the number of installments for this plan
     *
     * @api
     * @return int
     */
    public function getInstallmentsCount();

    /**
     * Return whether the plan is a Pay Later / deferred plan
     *
     * @api
     * @return bool
     */
    public function isDeferred();

    /**
     * Return the deferred plan "type": 'D' for days- and 'M' for months-deferred
     *
     * @api
     * @return string|null
     */
    public function getDeferredType();

    /**
     * Return the number of days a payment is deferred in case of a Pay Later plan
     *
     * @api
     * @return int
     */
    public function getDeferredDays();

    /**
     * Return the number of months a payment is deferred in case of a Pay Later plan
     *
     * @api
     * @return int
     */
    public function getDeferredMonths();

    /**
     * Return the minimum amount this plan is enabled for
     *
     * @api
     * @return int
     */
    public function getMinimumAmount();

    /**
     * Return the maximum amount this plan is enabled for
     *
     * @api
     * @return int
     */
    public function getMaximumAmount();

    /**
     * Return the plan's ID key
     *
     * @api
     * @return Alma\MonthlyPayments\Api\Data\InstallmentInterface[]
     */
    public function getInstallments();
}
