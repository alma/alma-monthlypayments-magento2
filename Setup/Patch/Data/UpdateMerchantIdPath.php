<?php

namespace Alma\MonthlyPayments\Setup\Patch\Data;

use Alma\API\Entities\Merchant;
use Alma\API\RequestError;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Payment\Gateway\Config\Config as ConfigGateway;

/**
 *  Get merchant_id value and clone it in test_merchant_id and live_merchant_id by store view
 *  Delete old merchant_id config
 */
class UpdateMerchantIdPath implements DataPatchInterface
{
    private const  PATH_EQUAL = 'path = ?';
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var AlmaClient
     */
    private $almaClient;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @param Logger $logger
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigHelper $configHelper
     * @param AlmaClient $almaClient
     */
    public function __construct(
        Logger               $logger,
        ResourceConnection   $resourceConnection,
        ScopeConfigInterface $scopeConfig,
        ConfigHelper         $configHelper,
        AlmaClient           $almaClient
    ) {
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
        $this->almaClient = $almaClient;
        $this->scopeConfig = $scopeConfig;
        $this->configHelper = $configHelper;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
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
        $oldMerchantIdPath = sprintf(ConfigGateway::DEFAULT_PATH_PATTERN, Config::CODE, 'merchant_id');

        $tableName = $this->getCoreTable();
        $oldMerchantIdQuery = $this->resourceConnection->getConnection()->select()
            ->from($tableName, ['value'])->where(self::PATH_EQUAL, $oldMerchantIdPath);

        $oldMerchant = new Merchant(
            ['id' => $this->resourceConnection->getConnection()->fetchOne($oldMerchantIdQuery)]
        );

        $apiQuery = $this->resourceConnection->getConnection()->select()
            ->from($tableName, ['config_id', 'scope', 'scope_id', 'path', 'value'])
            ->where(self::PATH_EQUAL, 'payment/alma_monthly_payments/live_api_key')
            ->orWhere(self::PATH_EQUAL, 'payment/alma_monthly_payments/test_api_key');

        $apiKeysRows = $this->resourceConnection->getConnection()->fetchAll($apiQuery);

        foreach ($apiKeysRows as $apiKeysRow) {
            $this->saveNewMerchantId($apiKeysRow, $oldMerchant);
        }
        $this->configHelper->deleteConfig($oldMerchantIdPath, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
        $this->configHelper->deleteConfig('fully_configured', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
    }

    /**
     * Get core table name with prefix
     *
     * @return string
     */
    private function getCoreTable(): string
    {
        return $this->resourceConnection->getTableName('core_config_data');
    }

    /**
     * Save merchant id in test_merchant_id and live_merchant_id
     *
     * @param array $apiKeysRow
     * @param Merchant $oldMerchant
     * @return array
     * @throws RequestError
     */
    protected function saveNewMerchantId(array $apiKeysRow, Merchant $oldMerchant): array
    {
        preg_match('/(live|test)/', $apiKeysRow['path'], $matches);
        $apiKey = $this->scopeConfig->getValue(
            $apiKeysRow['path'],
            $apiKeysRow['scope'],
            $apiKeysRow['scope_id']
        );
        $apiMode = $matches[0];
        try {
            $merchant = $this->almaClient->createInstance(
                $apiKey,
                $apiMode
            )->merchants->me();
            $this->configHelper->saveMerchantId(
                $apiMode . '_merchant_id',
                $merchant,
                $apiKeysRow['scope'],
                $apiKeysRow['scope_id']
            );
        } catch (AlmaClientException $e) {
            $this->logger->error('Error in upgrade data', [$e->getMessage()]);
            $this->configHelper->saveMerchantId(
                $apiMode . '_merchant_id',
                $oldMerchant,
                $apiKeysRow['scope'],
                $apiKeysRow['scope_id']
            );
        }
        return $matches;
    }
}
