<?php

namespace Alma\MonthlyPayments\Ui\DataProvider\Insurance\Subscription;

use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param $mainTable
     * @param ApiConfigHelper $apiConfigHelper
     * @param $resourceModel
     * @param $identifierName
     * @param $connectionName
     * @throws LocalizedException
     */
    public function __construct(
        EntityFactory   $entityFactory,
        Logger          $logger,
        FetchStrategy   $fetchStrategy,
        EventManager    $eventManager,
        ApiConfigHelper $apiConfigHelper,
        $mainTable = 'alma_insurance_subscription',
        $resourceModel = null,
        $identifierName = null,
        $connectionName = null
    ) {
        $this->apiConfigHelper = $apiConfigHelper;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }

    /**
     * Override _initSelect to add custom columns
     *
     * @return void
     */
    protected function _initSelect()
    {
        $this->addFilterToMap(
            'entity_id',
            'main_table.entity_id'
        );

        parent::_initSelect();
        $this->getSelect()
            ->joinLeft(
                ['sales_order' => $this->getConnection()->getTableName('sales_order')],
                'main_table.order_id = sales_order.entity_id',
                ['increment_id']
            );
    }
}
