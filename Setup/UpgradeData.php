<?php
namespace Alma\MonthlyPayments\Setup;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Adminhtml\Config\ApiUrl\CustomerCancelUrl;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Payment\Gateway\Config\Config as ConfigGateway;
use \Exception;

class UpgradeData implements UpgradeDataInterface
{
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

    public function __construct(
        Logger $logger,
        CustomerCancelUrl $customerCancelUrl,
        ResourceConnection $resourceConnection
    )
    {
        $this->logger = $logger;
        $this->customerCancelUrl = $customerCancelUrl;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritDoc
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $version = $context->getVersion();
        if (version_compare($version, '2.3.2') < 0) {
            $path = sprintf(ConfigGateway::DEFAULT_PATH_PATTERN, Config::CODE, ApiConfigHelper::CONFIG_CUSTOMER_CANCEL_URL);
            $oldProcessedPath = $this->customerCancelUrl->getOldDefaultUrl();
            $newProcessedPath = $this->customerCancelUrl->processValue('');

            $tableName = $this->resourceConnection->getTableName('core_config_data');
            $replace = ['value'=> $newProcessedPath];
            $where = ['path = ?' => $path,'value = ?'=>$oldProcessedPath];
            try {
                $this->resourceConnection->getConnection()->update($tableName,$replace,$where);
            } catch (Exception $e) {
                $this->logger->info('UpgradeData Exception : ',[$e->getMessage()]);
            }
        }
    }
}
