<?php

namespace Alma\MonthlyPayments\Helpers;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;


class ShareOfCheckoutHelper
{
    public const TOTAL_COUNT_KEY="total_order_count";
    public const TOTAL_AMOUNT_KEY="total_amount";
    public const CURRENCY_KEY="currency";
    public const PAYMENT_METHOD_KEY="payment_method_name";
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var array|OrderSearchResultInterface
     */
    private $orderCollection;
    /**
     * @var array
     */
    private $totalShareOfCheckoutOrders;
    /**
     * @var OrderHelper
     */
    private $orderHelper;
    /**
     * @var array
     */
    private $totalShareOfCheckoutCheckouts;

    public function __construct(
        Logger $logger,
        CollectionFactory $collectionFactory,
        OrderHelper $orderHelper
    )
    {
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->orderCollection = [];
        $this->totalShareOfCheckoutOrders = [];
        $this->totalShareOfCheckoutCheckouts = [];
        $this->orderHelper = $orderHelper;
    }

    public function getShareOfCheckoutOrderCollection():OrderSearchResultInterface
    {
        if(count($this->orderCollection)){
            $this->logger->info('Is already Set',[]);
            return $this->orderCollection;
        }
        $this->orderCollection = $this->collectionFactory->create()->addAttributeToSelect('*')->addFieldToFilter('created_at', [
            'from' => [$this->getShareOfCheckoutFromDate()],
            'to' => [$this->toShareOfCheckoutDate()],
        ])->addFieldToFilter('state',['in'=>['processing','complete']]);
        return $this->orderCollection;
    }

    public function countShareOfCheckoutOrders():int
    {
        if(count($this->orderCollection)){
            return $this->orderCollection->count();
        }
        return $this->getShareOfCheckoutOrderCollection()->count();
    }

    private function getTotalsOrders():array
    {
        if(count($this->totalShareOfCheckoutOrders)){
             return $this->totalShareOfCheckoutOrders;
        }

        $this->checkOrderCollectionExist();


        $ordersByCurrency = [];
        /** @var OrderInterface $order */
        foreach ($this->orderCollection as $order)
        {
            $currency = $this->orderHelper->getOrderCurrency($order);
            if (!isset($ordersByCurrency[$currency])){
                $ordersByCurrency[$currency]=$this->initOrderResult($currency);
            }
            $ordersByCurrency[$currency][self::TOTAL_AMOUNT_KEY] += $this->orderHelper->getOrderPaymentAmount($order);
            $ordersByCurrency[$currency][self::TOTAL_COUNT_KEY] ++ ;
        }
        $this->totalShareOfCheckoutOrders = array_values($ordersByCurrency);
        return $this->totalShareOfCheckoutOrders;
    }

    private function getTotalsCheckouts():array
    {
        if(count($this->totalShareOfCheckoutCheckouts)){
            return $this->totalShareOfCheckoutCheckouts;
        }

        $this->checkOrderCollectionExist();

        $ordersByCheckouts = [];
        /** @var OrderInterface $order */
        foreach ($this->orderCollection as $order)
        {
            $paymentMethodCode = $this->orderHelper->getOrderPaymentMethodCode($order);
            if(!isset($ordersByCheckouts[$paymentMethodCode])){
                $ordersByCheckouts[$paymentMethodCode]=['orders'=>[]];
            }
            $currency = $this->orderHelper->getOrderCurrency($order);
            if(!isset($ordersByCheckouts[$paymentMethodCode]['orders'][$currency])){
                $ordersByCheckouts[$paymentMethodCode]['orders'][$currency]=$this->initOrderResult($currency);
            }
            $ordersByCheckouts[$paymentMethodCode][self::PAYMENT_METHOD_KEY] = $paymentMethodCode;
            $ordersByCheckouts[$paymentMethodCode]['orders'][$currency][self::TOTAL_AMOUNT_KEY] += $this->orderHelper->getOrderPaymentAmount($order);
            $ordersByCheckouts[$paymentMethodCode]['orders'][$currency][self::TOTAL_COUNT_KEY] ++;
        }
        foreach ($ordersByCheckouts as $paymentKey => $paymentMethodOrders)
        {
            $ordersByCheckouts[$paymentKey]['orders']= array_values($paymentMethodOrders['orders']);
        }
        $this->totalShareOfCheckoutCheckouts = array_values($ordersByCheckouts);
        return $this->totalShareOfCheckoutCheckouts;
    }

    private function initOrderResult($currency):array
    {
        return [self::TOTAL_AMOUNT_KEY=>0,self::TOTAL_COUNT_KEY=>0,self::CURRENCY_KEY=>$currency];
    }

    private function checkOrderCollectionExist():void
    {
        if(!count($this->orderCollection)){
            $this->getShareOfCheckoutOrderCollection();
        }
    }

    private function getShareOfCheckoutFromDate():string
    {
        return '2022-03-15 00:00:00';
    }

    private function toShareOfCheckoutDate():string
    {
        return '2022-03-22 23:59:59';
    }

    public function getPayload(): array
    {
        return [
            "start_time"=> $this->getShareOfCheckoutFromDate(),
            "end_time"  => $this->toShareOfCheckoutDate(),
            "orders"    => $this->getTotalsOrders(),
            "checkouts" => $this->getTotalsCheckouts()
        ];
    }

}
