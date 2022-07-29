<?php

namespace Alma\MonthlyPayments\Setup;

use Alma\API\Entities\Merchant;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Adminhtml\Config\ApiUrl\CustomerCancelUrl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Payment\Gateway\Config\Config as ConfigGateway;
use \Exception;
use stdClass;

class UpgradeData implements UpgradeDataInterface
{
    const PATH_EQUAL = 'path = ?';

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
     * @param CustomerCancelUrl $customerCancelUrl
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigHelper $configHelper
     * @param AlmaClient $almaClient
     */
    public function __construct(
        Logger $logger,
        CustomerCancelUrl $customerCancelUrl,
        ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig,
        ConfigHelper $configHelper,
        AlmaClient $almaClient
    ) {
        $this->logger = $logger;
        $this->customerCancelUrl = $customerCancelUrl;
        $this->resourceConnection = $resourceConnection;
        $this->almaClient = $almaClient;
        $this->scopeConfig = $scopeConfig;
        $this->configHelper = $configHelper;
    }

    /**
     * @inheritDoc
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $version = $context->getVersion();
        if (version_compare($version, '2.5.0', '<')) {
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
        if (version_compare($version, '2.8.3', '<')) {
            $oldMerchantIdPath = sprintf(ConfigGateway::DEFAULT_PATH_PATTERN, Config::CODE, 'merchant_id');

            $tableName = $this->getCoreTable();
            $oldMerchantIdQuery = $this->resourceConnection->getConnection()->select()
                ->from($tableName, ['value'])->where(self::PATH_EQUAL, $oldMerchantIdPath);

            $oldMerchant = new Merchant(['id' => $this->resourceConnection->getConnection()->fetchOne($oldMerchantIdQuery)]);

            $apiQuery = $this->resourceConnection->getConnection()->select()
                ->from($tableName, ['config_id','scope','scope_id', 'path', 'value'])
                ->where(self::PATH_EQUAL, 'payment/alma_monthly_payments/live_api_key')
                ->orWhere(self::PATH_EQUAL, 'payment/alma_monthly_payments/test_api_key');

            $apiKeysRows = $this->resourceConnection->getConnection()->fetchAll($apiQuery);

            foreach ($apiKeysRows as $apiKeysRow) {
                preg_match('/(live|test)/', $apiKeysRow['path'], $matches);
                $apiKey = $this->scopeConfig->getValue($apiKeysRow['path'], $apiKeysRow['scope'], $apiKeysRow['scope_id']);
                $apiMode = $matches[0];
                try {
                    $merchant = $this->almaClient->createInstance($apiKey, $apiMode)->merchants->me();
                    $this->configHelper->saveMerchantId($apiMode . '_merchant_id', $merchant, $apiKeysRow['scope'], $apiKeysRow['scope_id']);
                } catch (AlmaClientException $e) {
                    $this->logger->error('Error in upgrade data', [$e->getMessage()]);
                    $this->configHelper->saveMerchantId($apiMode . '_merchant_id', $oldMerchant, $apiKeysRow['scope'], $apiKeysRow['scope_id']);
                }
            }
            $this->configHelper->deleteConfig($oldMerchantIdPath, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
            $this->configHelper->deleteConfig('fully_configured', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
        }
    }

    private function getCoreTable(): string
    {
        return $this->resourceConnection->getTableName('core_config_data');
    }
}
