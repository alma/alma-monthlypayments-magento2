<?php

namespace Alma\MonthlyPayments\Helpers\ShareOfCheckout;

use Alma\MonthlyPayments\Helpers\OrderHelper as GlobalOrderHelper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class OrderHelper extends AbstractHelper
{
    const TOTAL_COUNT_KEY = "total_order_count";
    const TOTAL_AMOUNT_KEY = "total_amount";
    const CURRENCY_KEY = "currency";
    const COUNT_KEY = "order_count";
    const AMOUNT_KEY = "amount";
    const PAYMENT_METHOD_KEY = "payment_method_name";

    /**
     * @var OrderSearchResultInterface|null
     */
    private $orderCollection = null;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var GlobalOrderHelper
     */
    private $orderHelper;
    /**
     * @var DateHelper
     */
    private $dateHelper;



    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param GlobalOrderHelper $orderHelper
     * @param DateHelper $dateHelper
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        GlobalOrderHelper $orderHelper,
        DateHelper $dateHelper
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->orderHelper = $orderHelper;
        $this->dateHelper = $dateHelper;
    }

    /**
     * @return array
     */
    public function getTotalsOrders(): array
    {
        $ordersByCurrency = [];
        /** @var OrderInterface $order */
        foreach ($this->getOrderCollection() as $order) {
            $currency = $this->orderHelper->getOrderCurrency($order);
            if (!isset($ordersByCurrency[$currency])) {
                $ordersByCurrency[$currency] = $this->initTotalOrderResult($currency);
            }
            $ordersByCurrency[$currency][self::TOTAL_AMOUNT_KEY] += $this->orderHelper->getOrderPaymentAmount($order);
            $ordersByCurrency[$currency][self::TOTAL_COUNT_KEY] ++ ;
        }
        return array_values($ordersByCurrency);
    }

    /**
     * @return array
     */
    public function getShareOfCheckoutByPaymentMethods(): array
    {
        $ordersByCheckouts = [];
        /** @var OrderInterface $order */
        foreach ($this->getOrderCollection() as $order) {
            $paymentMethodCode = $this->orderHelper->getOrderPaymentMethodCode($order);
            if (!isset($ordersByCheckouts[$paymentMethodCode])) {
                $ordersByCheckouts[$paymentMethodCode] = ['orders' => []];
            }
            $currency = $this->orderHelper->getOrderCurrency($order);
            if (!isset($ordersByCheckouts[$paymentMethodCode]['orders'][$currency])) {
                $ordersByCheckouts[$paymentMethodCode]['orders'][$currency] = $this->initOrderResult($currency);
            }
            $ordersByCheckouts[$paymentMethodCode][self::PAYMENT_METHOD_KEY] = $paymentMethodCode;
            $ordersByCheckouts[$paymentMethodCode]['orders'][$currency][self::AMOUNT_KEY] += $this->orderHelper->getOrderPaymentAmount($order);
            $ordersByCheckouts[$paymentMethodCode]['orders'][$currency][self::COUNT_KEY] ++;
        }
        foreach ($ordersByCheckouts as $paymentKey => $paymentMethodOrders) {
            $ordersByCheckouts[$paymentKey]['orders'] = array_values($paymentMethodOrders['orders']);
        }
        return array_values($ordersByCheckouts);
    }

    /**
     * @param string $currency
     * @return array
     */
    public function initTotalOrderResult(string $currency): array
    {
        return [self::TOTAL_AMOUNT_KEY => 0, self::TOTAL_COUNT_KEY => 0, self::CURRENCY_KEY => $currency];
    }

    /**
     * @param string $currency
     * @return array
     */
    public function initOrderResult(string $currency): array
    {
        return [self::AMOUNT_KEY => 0, self::COUNT_KEY => 0, self::CURRENCY_KEY => $currency];
    }

    /**
     * @param OrderSearchResultInterface|null $orderCollection
     *
     * @return void
     */
    public function setOrderCollection(?OrderSearchResultInterface $orderCollection): void
    {
        $this->orderCollection = $orderCollection;
    }

    /**
     * @return OrderSearchResultInterface|null
     */
    public function getOrderCollection(): ?OrderSearchResultInterface
    {
        if (!$this->orderCollection) {
            $this->createOrderCollection();
        }
        return $this->orderCollection;
    }

    /**
     * @return void
     */
    public function createOrderCollection(): void
    {
        $collection = $this->collectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('created_at', [
                'from' => [$this->dateHelper->getStartDate()],
                'to' => [$this->dateHelper->getEndDate()],
            ])
            ->addFieldToFilter('state', ['in' => ShareOfCheckoutHelper::SHARED_ORDER_STATES]);
        $this->setOrderCollection($collection);
    }

    /**
     * @return void
     */
    public function flushOrderCollection(): void
    {
        $this->orderCollection = null;
    }

}
