<?php

namespace Alma\MonthlyPayments\Ui\Component\Insurance\Listing\Column;

use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription\CollectionFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class OrderContainSubscription extends Column
{
    private $collection;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CollectionFactory $collection
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        CollectionFactory  $collection,
        array              $components = [],
        array              $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->collection = $collection;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        $ordersIds = $this->extractOrdersIds($dataSource);
        $subscriptionOrderId = $this->getSubscriptionOrderId($ordersIds);

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (in_array($item['entity_id'], $subscriptionOrderId)) {
                    $item[$this->getData('name')] = __('Yes');
                } else {
                    $item[$this->getData('name')] = __('No');
                }
                if (!$item['total_paid']) {
                    $item[$this->getData('name')] = __('Unknown');
                }
            }
        }

        return $dataSource;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    private function extractOrdersIds(array $dataSource): array
    {
        $ordersIds = [];
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $ordersIds[] = $item['entity_id'];
            }
        }
        return $ordersIds;
    }

    /**
     * @param array $ordersIds
     * @return array
     */
    private function getSubscriptionOrderId(array $ordersIds): array
    {
        $collection = $this->collection->create();
        $collection->addFieldToFilter('order_id', ['in' => $ordersIds]);

        $subscriptionOrderId = [];
        foreach ($collection as $subscription) {
            $subscriptionOrderId[] = $subscription->getOrderId();
        }
        return $subscriptionOrderId;
    }
}
