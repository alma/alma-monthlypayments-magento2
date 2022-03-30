<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\RequestError;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;


class ShareOfCheckoutHelper
{
    public const TOTAL_COUNT_KEY="total_order_count";
    public const TOTAL_AMOUNT_KEY="total_amount";
    public const CURRENCY_KEY="currency";
    public const PAYMENT_METHOD_KEY="payment_method_name";
    public const SHARED_ORDER_STATES=['processing','complete'];
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
    /**
     * @var AlmaClient
     */
    private $almaClient;
    /**
     * @var null
     */
    private $startTime;
    /**
     * @var null
     */
    private $endTime;

    public function __construct(
        Logger $logger,
        CollectionFactory $collectionFactory,
        OrderHelper $orderHelper,
        AlmaClient $almaClient
    )
    {
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->orderCollection = [];
        $this->totalShareOfCheckoutOrders = [];
        $this->totalShareOfCheckoutCheckouts = [];
        $this->orderHelper = $orderHelper;
        $this->almaClient = $almaClient->getDefaultClient();
        $this->startTime = null;
        $this->endTime = null;
    }

    /**
     * @throws RequestError
     */
    public function shareDay()
    {
        if (!$this->almaClient){
            throw new \InvalidArgumentException('Alma client is not define');
        }
        $res=[];
        try {
            $res = $this->almaClient->shareOfCheckout->share($this->getPayload());
        } catch (RequestError $e) {
            $this->logger->info('ShareOfCheckoutHelper::share error get message :',[$e->getMessage()]);
            throw new RequestError($e->getMessage(), null, $res);
        } finally {
            $this->writeLogs();
            $this->flushOrderCollection();
        }
        return $res;
    }

    public function countShareOfCheckoutOrders():int
    {
        return $this->getShareOfCheckoutOrderCollection()->count();
    }

    public function getLastUpdateDate():string
    {
        // TODO - Create api call
        $lastUpdateByApi = null;
        if (isset($lastUpdateByApi)){
            return $lastUpdateByApi;
        }
        return date('Y-m-d',strtotime('-2 days'));
    }

    public function setShareOfCheckoutFromDate($startTime):void
    {
        $this->startTime = $startTime.' 00:00:00';
        $this->setShareOfCheckoutToDate($startTime);
    }

    public function setShareOfCheckoutToDate($endTime):void
    {
        $this->endTime = $endTime.' 23:59:59';
    }

    private function getShareOfCheckoutOrderCollection():OrderSearchResultInterface
    {
        if(count($this->orderCollection)){
            return $this->orderCollection;
        }
        $this->orderCollection = $this->collectionFactory->create()->addAttributeToSelect('*')->addFieldToFilter('created_at', [
            'from' => [$this->getShareOfCheckoutFromDate()],
            'to' => [$this->getShareOfCheckoutToDate()],
        ])->addFieldToFilter('state',['in'=> self::SHARED_ORDER_STATES]);
        return $this->orderCollection;
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

    private function getTotalsPaymentMethods():array
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
        if(isset($this->startTime)){
            return $this->startTime;
        }

        return date('Y-m-d',strtotime('yesterday')).' 00:00:00';
    }

    private function getShareOfCheckoutToDate():string
    {
        if(isset($this->endTime)){
            return $this->endTime;
        }
        return date('Y-m-d',strtotime('yesterday')).' 23:59:59';
    }

    private function getPayload(): array
    {
        return [
            "start_time"=> $this->getShareOfCheckoutFromDate(),
            "end_time"  => $this->getShareOfCheckoutToDate(),
            "orders"    => $this->getTotalsOrders(),
            "payment_methods" => $this->getTotalsPaymentMethods()
        ];
    }

    public function flushOrderCollection():void
    {
        $this->orderCollection = [];
        $this->totalShareOfCheckoutOrders = [];
        $this->totalShareOfCheckoutCheckouts = [];
    }

    private function writeLogs():void
    {
        $this->logger->info('Share start date',[$this->getShareOfCheckoutFromDate()]);
        $this->logger->info('Orders send',[$this->countShareOfCheckoutOrders()]);
    }

}
