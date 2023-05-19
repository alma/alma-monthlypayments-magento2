<?php
/**
 * 2018 Alma / Nabla SAS
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
 * @author    Alma / Nabla SAS <contact@getalma.eu>
 * @copyright 2018 Alma / Nabla SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 *
 */

namespace Alma\MonthlyPayments\Helpers;

class Functions
{
    /**
     * @param $amount
     * @return float
     */
    public static function priceFromCents($amount)
    {
        return (float)($amount / 100);
    }

    /**
     * @param $price
     * @return int
     */
    public static function priceToCents($price): int
    {
        return (int)(round($price * 100));
    }

    /**
    * Get payment type with plankey
    *
    * @param string $planKey
    * @return string
     */
    public static function getPaymentType(string $planKey): string
    {
        $matches = [];
        $isKnownType = preg_match('/^general:(\d{1,2}):(\d{1,2}):(\d{1,2})$/', $planKey, $matches);

        if ($isKnownType) {
            $installmentCount = $matches[1];
            $isDeferred = $matches[2] > 0 || $matches[3] > 0;

            return self::buildType($installmentCount, $isDeferred);
        }
        // We don't know this paymentType
        return 'other';
    }

    /**
     * Build type for according to installment count and is deferred flag
     *
     * @param int $installmentCount
     * @param bool $isDeferred
     *
     * @return string
     */
    private static function buildType(int $installmentCount, bool $isDeferred): string
    {
        $type = 'other';

        if ($installmentCount >= 1 && !$isDeferred) {
            $type = Eligibility::INSTALLMENTS_TYPE;
        }
        if ($installmentCount > 4 && !$isDeferred) {
            $type = Eligibility::SPREAD_TYPE;
        }
        if ($installmentCount == 1 && $isDeferred) {
            $type = Eligibility::DEFERRED_TYPE;
        }
        return $type;
    }
}
