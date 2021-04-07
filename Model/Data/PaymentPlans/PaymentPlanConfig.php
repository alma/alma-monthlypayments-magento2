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

namespace Alma\MonthlyPayments\Model\Data\PaymentPlans;

class PaymentPlanConfig
{
    const TRANSIENT_KEY_MIN_ALLOWED_AMOUNT = 'minAllowedAmount';
    const TRANSIENT_KEY_MAX_ALLOWED_AMOUNT = 'maxAllowedAmount';
    const TRANSIENT_KEY_MERCHANT_FEES = 'merchantFees';
    const TRANSIENT_KEY_CUSTOMER_FEES = 'customerFees';

    /**
     * @return string[]
     */
    public static function transientKeys(): array
    {
        return [
            self::TRANSIENT_KEY_MIN_ALLOWED_AMOUNT,
            self::TRANSIENT_KEY_MAX_ALLOWED_AMOUNT,
            self::TRANSIENT_KEY_MERCHANT_FEES,
            self::TRANSIENT_KEY_CUSTOMER_FEES,
        ];
    }

    /**
     * @var array
     */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function isAllowed(): bool
    {
        return $this->data['allowed'];
    }

    public function isEnabled(): bool
    {
        return $this->data['enabled'];
    }

    public function installmentsCount(): int
    {
        return $this->data['installmentsCount'];
    }

    public function isDeferred(): bool
    {
        return $this->deferredDays() > 0 || $this->deferredMonths() > 0;
    }

    public function deferredType(): string
    {
        return $this->deferredMonths() > 0 ? 'M' : 'D';
    }

    public function deferredDays(): int
    {
        return $this->data['deferredDays'];
    }

    public function deferredMonths(): int
    {
        return $this->data['deferredMonths'];
    }

    /**
     * Returns deferred duration in days – approximate value (invariably using 30 days for 1 month) but it's OK as it's
     * mainly being used for sorting purposes.
     *
     * @return int
     */
    public function deferredDurationInDays()
    {
        return $this->deferredMonths() * 30 + $this->deferredDays();
    }

    public function deferredDuration(): int
    {
        return $this->deferredMonths() ?: $this->deferredDays();
    }

    public function minimumAmount(): int
    {
        return $this->data['minAmount'];
    }

    public function minimumAllowedAmount(): int
    {
        return $this->data['minAllowedAmount'];
    }

    public function maximumAmount(): int
    {
        return $this->data['maxAmount'];
    }

    public function maximumAllowedAmount(): int
    {
        return $this->data['maxAllowedAmount'];
    }

    public function variableMerchantFees(): int
    {
        return $this->data['merchantFees']['variable'];
    }

    public function fixedMerchantFees(): int
    {
        return $this->data['merchantFees']['fixed'];
    }

    public function variableCustomerFees(): int
    {
        return $this->data['customerFees']['variable'];
    }

    public function fixedCustomerFees(): int
    {
        return $this->data['customerFees']['fixed'];
    }
}
