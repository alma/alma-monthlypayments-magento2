<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\RequestError;
use InvalidArgumentException;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;

class ShareOfCheckoutHelper extends AbstractHelper
{
    const TOTAL_COUNT_KEY = "total_order_count";
    const TOTAL_AMOUNT_KEY = "total_amount";
    const COUNT_KEY = "order_count";
    const AMOUNT_KEY = "amount";
    const CURRENCY_KEY = "currency";
    const PAYMENT_METHOD_KEY = "payment_method_name";
    const SHARED_ORDER_STATES = ['processing', 'complete'];
    const SHARE_CHECKOUT_ENABLE_KEY = 'share_checkout_enable';
    const SHARE_CHECKOUT_DATE_KEY = 'share_checkout_date';


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
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @param Logger $logger
     * @param CollectionFactory $collectionFactory
     * @param OrderHelper $orderHelper
     * @param AlmaClient $almaClient
     * @param Context $context
     * @param WriterInterface $configWriter
     */
    public function __construct(
        Logger $logger,
        CollectionFactory $collectionFactory,
        OrderHelper $orderHelper,
        AlmaClient $almaClient,
        Context $context,
        WriterInterface $configWriter
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->orderCollection = [];
        $this->totalShareOfCheckoutOrders = [];
        $this->totalShareOfCheckoutCheckouts = [];
        $this->orderHelper = $orderHelper;
        $this->almaClient = $almaClient->getDefaultClient();
        $this->startTime = null;
        $this->endTime = null;
        $this->configWriter = $configWriter;
    }

    /**
     * @return bool
     */
    public function shareOfCheckoutIsEnabled(): bool
    {
        return $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . self::SHARE_CHECKOUT_ENABLE_KEY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param string $date
     * @return void
     * @throws RequestError
     */
    public function shareDay(string $date): void
    {
        $this->startTime = $date.' 00:00:00';
        $this->endTime   = $date.' 23:59:59';

        if (!$this->almaClient) {
            throw new InvalidArgumentException('Alma client is not define');
        }
        $res = null;
        try {
            $payload = $this->getPayload();
            $this->logger->info('Share of checkout payload', [$payload]);
            $this->almaClient->shareOfCheckout->share($this->getPayload());
        } catch (RequestError $e) {
            throw new RequestError($e->getMessage(), null, $res);
        } finally {
            $this->writeLogs();
            $this->flushOrderCollection();
        }
    }

    /**
     * @return string
     * @throws RequestError
     */
    public function getLastUpdateDate(): string
    {
        if (!$this->almaClient) {
            throw new InvalidArgumentException('Alma client is not define');
        }
        try {
            $lastUpdateByApi = $this->almaClient->shareOfCheckout->getLastUpdateDates();
            return date('Y-m-d', $lastUpdateByApi['end_time']);
        } catch (RequestError $e) {
            if ($e->response->responseCode == '404') {
                return date('Y-m-d', strtotime('-2 days'));
            }
            throw new RequestError($e->getMessage(), null);
        }
    }

    /**
     * @return OrderSearchResultInterface
     */
    private function getOrderCollection(): OrderSearchResultInterface
    {
        if (count($this->orderCollection)) {
            return $this->orderCollection;
        }
        $this->orderCollection = $this->collectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('created_at', [
                'from' => [$this->getStartDate()],
                'to' => [$this->getEndDate()],
                ])
            ->addFieldToFilter('state', ['in'=> self::SHARED_ORDER_STATES]);
        return $this->orderCollection;
    }

    /**
     * @return array
     */
    private function getTotalsOrders(): array
    {
        if (count($this->totalShareOfCheckoutOrders)) {
            return $this->totalShareOfCheckoutOrders;
        }

        $ordersByCurrency = [];
        /** @var OrderInterface $order */
        foreach ($this->getOrderCollection() as $order) {
            $currency = $this->orderHelper->getOrderCurrency($order);
            if (!isset($ordersByCurrency[$currency])) {
                $ordersByCurrency[$currency]=$this->initTotalOrderResult($currency);
            }
            $ordersByCurrency[$currency][self::TOTAL_AMOUNT_KEY] += $this->orderHelper->getOrderPaymentAmount($order);
            $ordersByCurrency[$currency][self::TOTAL_COUNT_KEY] ++ ;
        }
        $this->totalShareOfCheckoutOrders = array_values($ordersByCurrency);
        return $this->totalShareOfCheckoutOrders;
    }

    /**
     * @return array
     */
    private function getShareOfCheckoutByPaymentMethods(): array
    {
        if (count($this->totalShareOfCheckoutCheckouts)) {
            return $this->totalShareOfCheckoutCheckouts;
        }

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
        $this->totalShareOfCheckoutCheckouts = array_values($ordersByCheckouts);
        return $this->totalShareOfCheckoutCheckouts;
    }

    /**
     * @param string $currency
     * @return array
     */
    private function initOrderResult(string $currency): array
    {
        return [self::AMOUNT_KEY => 0, self::COUNT_KEY => 0, self::CURRENCY_KEY => $currency];
    }
    /**
     * @param $currency
     * @return array
     */
    private function initTotalOrderResult($currency): array
    {
        return [self::TOTAL_AMOUNT_KEY => 0, self::TOTAL_COUNT_KEY => 0, self::CURRENCY_KEY => $currency];
    }

    /**
     * @return string
     */
    private function getStartDate(): string
    {
        if (isset($this->startTime)) {
            return $this->startTime;
        }
        return date('Y-m-d', strtotime('yesterday')).' 00:00:00';
    }

    /**
     * @return string
     */
    private function getEndDate(): string
    {
        if (isset($this->endTime)) {
            return $this->endTime;
        }
        return date('Y-m-d', strtotime('yesterday')).' 23:59:59';
    }

    /**
     * @return array
     */
    private function getPayload(): array
    {
        return [
            "start_time"=> $this->getStartDate(),
            "end_time"  => $this->getEndDate(),
            "orders"    => $this->getTotalsOrders(),
            "payment_methods" => $this->getShareOfCheckoutByPaymentMethods()
        ];
    }

    /**
     * @return void
     */
    public function flushOrderCollection(): void
    {
        $this->orderCollection = [];
        $this->totalShareOfCheckoutOrders = [];
        $this->totalShareOfCheckoutCheckouts = [];
    }

    /**
     * @return string
     */
    public function getShareOfCheckoutEnabledDate(): string
    {
        $shareOfCheckoutEnabledDate = $this->scopeConfig->getValue(
            $this->getShareOfCheckoutDateKey(),
            ScopeInterface::SCOPE_STORE
        );
        if ($shareOfCheckoutEnabledDate == '') {
            $this->logger->info('No enable date in config', []);
            throw new InvalidArgumentException('No enable date in config');
        }
        return $shareOfCheckoutEnabledDate;
    }

    /**
     * @return void
     */
    private function writeLogs(): void
    {
        $this->logger->info('Share start date', [$this->getStartDate()]);
        $this->logger->info('Orders send', [$this->getOrderCollection()->count()]);
    }

    /**
     * @param string $date
     * @return void
     */
    public function saveShareOfCheckoutDate(string $date): void
    {
        $this->configWriter->save($this->getShareOfCheckoutDateKey(), $date);
    }

    /**
     * @return void
     */
    public function deleteShareOfCheckoutDate(): void
    {
        $this->configWriter->delete($this->getShareOfCheckoutDateKey());
    }

    /**
     * @return string
     */
    private function getShareOfCheckoutDateKey(): string
    {
        return ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . self::SHARE_CHECKOUT_DATE_KEY;
    }
}
