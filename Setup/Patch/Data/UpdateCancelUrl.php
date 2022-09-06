<?php

namespace Alma\MonthlyPayments\Setup\Patch\Data;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Adminhtml\Config\ApiUrl\CustomerCancelUrl;
use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Payment\Gateway\Config\Config as ConfigGateway;

class UpdateCancelUrl implements DataPatchInterface
{
    const PATH_EQUAL = 'path = ?';

    private Logger $logger;
    private CustomerCancelUrl $customerCancelUrl;
    private ResourceConnection $resourceConnection;

    public function __construct(
        Logger $logger,
        CustomerCancelUrl $customerCancelUrl,
        ResourceConnection $resourceConnection
    ) {
        $this->logger = $logger;
        $this->customerCancelUrl = $customerCancelUrl;
        $this->resourceConnection = $resourceConnection;
    }

    public function getAliases()
    {
        return [];
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    public function apply()
    {
        $path = sprintf(ConfigGateway::DEFAULT_PATH_PATTERN, Config::CODE, Config::CONFIG_CUSTOMER_CANCEL_URL);
        $oldProcessedPath = $this->customerCancelUrl->getOldDefaultUrl();
        $newProcessedPath = $this->customerCancelUrl->processValue('');
        $tableName = $this->getCoreTable();
        $replace = ['value' => $newProcessedPath];
        $where = [self::PATH_EQUAL => $path, 'value = ?' => $oldProcessedPath];
        try {
            $this->resourceConnection->getConnection()->update($tableName, $replace, $where);
        } catch (Exception $e) {
            $this->logger->error('UpgradeData Exception : ', [$e->getMessage()]);
        }
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get Core table name with prefix
     *
     * @return string
     */
    private function getCoreTable(): string
    {
        return $this->resourceConnection->getTableName('core_config_data');
    }
}
