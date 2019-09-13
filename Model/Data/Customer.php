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

use Alma\MonthlyPayments\Helpers\Functions;
use Magento\Checkout\Model\Session;
use \Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class Customer
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var Session
     */
    private $checkoutSession;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Session $checkoutSession
    )
    {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param CustomerInterface|null $customer
     * @param AddressAdapterInterface[] $addresses
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     */
    public function dataFromCustomer($customer, $addresses)
    {
        if ($customer) {
            $customerData = [
                'first_name' => $customer->getFirstname(),
                'last_name' => $customer->getLastname(),
                'email' => $customer->getEmail(),
                'birth_date' => $customer->getDob(),
                'addresses' => [],
                'phone' => null,
                'metadata' => [],
            ];
        } else {
            $customerData = [
                'first_name' => null,
                'last_name' => null,
                'email' => null,
                'birth_date' => null,
                'addresses' => [],
                'phone' => null,
                'metadata' => [],
            ];
        }

        foreach ($addresses as $address) {
            // Backfill any missing customer attribute with data from addresses
            $map = [
                'first_name' => 'getFirstname',
                'last_name' => 'getLastname',
                'email' => 'getEmail',
                'birth_date' => 'getDob',
                'phone' => 'getTelephone'
            ];

            foreach ($map as $attribute => $method) {
                $callable = [$address, $method];
                if (method_exists($address, $method) && is_callable($callable) && !$customerData[$attribute]) {
                    $customerData[$attribute] = call_user_func_array($callable, []);
                }
            }
        }

        foreach ($addresses as $address) {
            $customerData['addresses'][] = Address::dataFromAddress($address);
        }

        if (!$customerData['email']) {
            try {
                $customerData['email'] = $this->checkoutSession->getQuote()->getCustomerEmail();
            } catch (\Exception $e) {
                // just ignore
            }
        }

        // Find all orders completed by the customer in the past
        $this->searchCriteriaBuilder
            ->addFilter('status', Order::STATE_COMPLETE)
            ->setSortOrders([
                new SortOrder([SortOrder::FIELD => "created_at", SortOrder::DIRECTION => SortOrder::SORT_DESC])
            ])
            ->setPageSize(100);

        if ($customer) {
            $this->searchCriteriaBuilder->addFilter('customer_id', $customer->getId());
        } elseif ($customerData['email']) {
            $this->searchCriteriaBuilder->addFilter('customer_email', $customerData['email']);
        } else {
            return $customerData;
        }

        $customerData['metadata']['past_orders'] = [];

        $criteria = $this->searchCriteriaBuilder->create();
        $orders = $this->orderRepository->getList($criteria)->getItems();

        foreach ($orders as $order) {
            $customerData['metadata']['past_orders'][] = [
                "id" => $order->getEntityId(),
                "amount" => Functions::priceToCents($order->getTotalPaid()),
                "date" => strtotime($order->getCreatedAt()),
                "payment_method" => $order->getPayment()->getMethod()
            ];
        }

        return $customerData;
    }
}
