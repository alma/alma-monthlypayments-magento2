<?php

namespace Alma\MonthlyPayments\Ui\Component\Insurance\Listing\Column;

use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class OrderContainSubscription extends Column
{
    private Logger $logger;

    public function __construct(
        Logger $logger,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {

        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->logger = $logger;
    }

    public function prepareDataSource(array $dataSource)
    {
        $this->logger->info('$datasource', [$dataSource]);
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = $item['increment_id'];
            }
        }
        return $dataSource;
    }
}
