<?php

namespace Alma\MonthlyPayments\Setup\Patch\Data;

use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 *  Get merchant_id value and clone it in test_merchant_id and live_merchant_id by store view
 *  Delete old merchant_id config
 */
class UpdateInPageDisplayConfiguration implements DataPatchInterface
{
    const PATH_EQUAL = 'path = ?';
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var AlmaClient
     */
    private $almaClient;

    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;

    public function __construct(
        Logger $logger,
        ConfigHelper $configHelper,
        ApiConfigHelper $apiConfigHelper,
        AlmaClient $almaClient
    ) {
        $this->logger = $logger;
        $this->almaClient = $almaClient;
        $this->configHelper = $configHelper;
        $this->apiConfigHelper = $apiConfigHelper;
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Call Alma merchant for key saved in BD and save cms_allow_inpage in DB
     *
     * @return void
     * @throws RequestError
     */
    public function apply(): void
    {
        $apiKeyArray = [
          'test' => $this->apiConfigHelper->getTestKey(),
          'live' => $this->apiConfigHelper->getLiveKey(),
        ];

        foreach ($apiKeyArray as $mode => $key) {
            if ($key !== '') {
                try {
                    $almaClient = $this->almaClient->createInstance($key, $mode);
                } catch (AlmaClientException $e) {
                    $this->logger->error('Error in Update data for In Page', [$e->getMessage()]);
                    return;
                }
                $merchant = $almaClient->merchants->me();
                $path = $mode . '_allowed_in_page';
                $this->configHelper->saveIsAllowedInPage($path, $merchant, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
            }
        }
    }
}
