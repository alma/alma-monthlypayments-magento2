<?php

namespace Alma\MonthlyPayments\Ui\Component\Insurance\Listing\Column;

use Alma\MonthlyPayments\Helpers\Functions;
use Alma\MonthlyPayments\Helpers\InsuranceSubscriptionHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Ui\Component\Listing\Columns\Column;

class SubscriptionColumnFormatter extends Column
{

    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var InsuranceSubscriptionHelper
     */
    private $subscriptionHelper;

    public function __construct(
        Logger                 $logger,
        InsuranceSubscriptionHelper $subscriptionHelper,
        ContextInterface       $context,
        UiComponentFactory     $uiComponentFactory,
        OrderRepository        $orderRepository,
        PriceCurrencyInterface $priceCurrency,
        array                  $components = [],
        array                  $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
        $this->orderRepository = $orderRepository;
        $this->priceCurrency = $priceCurrency;
        $this->logger = $logger;
        $this->subscriptionHelper = $subscriptionHelper;
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if ($item) {
                    $order = $this->orderRepository->get($item['order_id']);
                    $item['subscription_amount'] = $this->priceCurrency->convertAndFormat(
                        Functions::priceFromCents($item['subscription_amount']),
                        false,
                        2,
                        null,
                        $order->getOrderCurrencyCode()
                    );
                    $item['subscription_state'] = $this->subscriptionHelper->getNameStatus($item['subscription_state'] ?? '');
                }
            }
        }

        return $dataSource;
    }
}
