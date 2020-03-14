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

interface PaymentValidationResultInterface {
    /**
     * Return whether the considered payment is valid
     *
     * @api
     * @return bool
     */
    public function getValid();

    /**
     * Return the reason for not being valid
     *
     * @api
     * @return string|null
     */
    public function getReason();

    /**
     * Return the order reference for the validated payment (null if invalid)
     *
     * @api
     * @return string|null
     */
    public function getOrderRef();

    /**
     * Return the order id for the validated payment (null if invalid)
     *
     * @api
     * @return int|null
     */
    public function getOrderId();

    /**
     * Return the order date as a UNIX timestamp for the validated payment (null if invalid)
     *
     * @api
     * @return int|null
     */
    public function getOrderDate();

    /**
     * Return the order amount (in cents) for the validated payment (null if invalid)
     *
     * @api
     * @return int|null
     */
    public function getPurchaseAmount();

    /**
     * Set whether the considered payment is valid
     *
     * @api
     * @param bool $value
     * @return null
     */
    public function setValid($value);

    /**
     * Set the reason for not being valid
     *
     * @api
     * @param string $value
     * @return null
     */
    public function setReason($value);

    /**
     * Set the order reference for the validated payment (null if invalid)
     *
     * @api
     * @param string $value
     * @return null
     */
    public function setOrderRef($value);

    /**
     * Set the order id for the validated payment (null if invalid)
     *
     * @api
     * @param int $value
     * @return null
     */
    public function setOrderId($value);

    /**
     * Set the order date as a UNIX timestamp for the validated payment (null if invalid)
     *
     * @api
     * @param int $value
     * @return null
     */
    public function setOrderDate($value);

    /**
     * Set the order amount (in cents) for the validated payment (null if invalid)
     *
     * @api
     * @param $value
     * @return null
     */
    public function setPurchaseAmount($value);
}
