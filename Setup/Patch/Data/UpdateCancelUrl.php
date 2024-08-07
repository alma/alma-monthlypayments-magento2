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

/**
 * Change old customer cancel url from checkout/cart to alma/payment/cancel/
 */
class UpdateCancelUrl implements DataPatchInterface
{
    private const PATH_EQUAL = 'path = ?';

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var CustomerCancelUrl
     */
    private $customerCancelUrl;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param Logger $logger
     * @param CustomerCancelUrl $customerCancelUrl
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Logger $logger,
        CustomerCancelUrl $customerCancelUrl,
        ResourceConnection $resourceConnection
    ) {
        $this->logger = $logger;
        $this->customerCancelUrl = $customerCancelUrl;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function apply(): void
    {
        try {
            $path = sprintf(ConfigGateway::DEFAULT_PATH_PATTERN, Config::CODE, Config::CONFIG_CUSTOMER_CANCEL_URL);
            $oldProcessedPath = $this->customerCancelUrl->getOldDefaultUrl();
            $newProcessedPath = $this->customerCancelUrl->processValue('');
            $tableName = $this->getCoreTable();
            $replace = ['value' => $newProcessedPath];
            $where = [self::PATH_EQUAL => $path, 'value = ?' => $oldProcessedPath];
            $this->resourceConnection->getConnection()->update($tableName, $replace, $where);
        } catch (Exception $e) {
            $this->logger->error('UpgradeData Exception : ', [$e->getMessage()]);
        }
    }

    /**
     * @inheritdoc
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
