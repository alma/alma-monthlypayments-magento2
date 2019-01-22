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

namespace Alma\MonthlyPayments\Model\Data;

use Magento\Payment\Gateway\Data\AddressAdapterInterface;

class Address
{
    public static function dataFromAddress(AddressAdapterInterface $address)
    {
        $data = [];
        $map = [
            'first_name' => 'getFirstname',
            'last_name' => 'getLastname',
            'company' => 'getCompany',
            'line1' => 'getStreetLine1',
            'line2' => 'getStreetLine2',
            'postal_code' => 'getPostcode',
            'city' => 'getCity',
            'country' => 'getCountryId',
            'email' => 'getEmail',
            'phone' => 'getTelephone'
        ];

        foreach ($map as $attribute => $method) {
            $callable = [$address, $method];
            if (method_exists($address, $method) && is_callable($callable)) {
                $data[$attribute] = call_user_func_array($callable, []);
            }
        }

        return $data;
    }

}
