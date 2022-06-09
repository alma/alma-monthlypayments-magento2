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

namespace Alma\MonthlyPayments\Gateway\Request;

use Alma\MonthlyPayments\Model\Data\Customer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class CustomerDataBuilder implements BuilderInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Customer
     */
    private $customerData;

    /**
     * CustomerDataBuilder constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param Customer $customerData
     */
    public function __construct(CustomerRepositoryInterface $customerRepository, Customer $customerData)
    {
        $this->customerRepository = $customerRepository;
        $this->customerData = $customerData;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $order = $paymentDO->getOrder();

        $customerId = $order->getCustomerId();
        $customer = null;
        if ($customerId) {
            $customer = $this->customerRepository->getById($customerId);
        }

        $addressArray = [];
        if ($order->getBillingAddress()) {
            $addressArray[] = $order->getBillingAddress();
        }
        if ($order->getShippingAddress()) {
            $addressArray[] = $order->getShippingAddress();
        }
        return [
            'customer' => $this->customerData->dataFromCustomer(
                $customer,
                $addressArray
            ),
        ];
    }
}
