<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Client;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ApiConfigHelper extends ConfigHelper
{
    const CONFIG_LIVE_API_KEY = 'live_api_key';
    const CONFIG_TEST_API_KEY = 'test_api_key';
    const CONFIG_FULLY_CONFIGURED = 'fully_configured';
    const CONFIG_API_MODE = 'api_mode';

    /**
     * @return mixed|null
     */
    public function getActiveAPIKey()
    {
        $mode = $this->getActiveMode();
        $apiKeyType = ($mode == Client::LIVE_MODE) ?
            self::CONFIG_LIVE_API_KEY :
            self::CONFIG_TEST_API_KEY ;
        return $this->getConfigByCode($apiKeyType);
    }

    /**
     * @param int | null $storeId
     *
     * @return mixed|null
     */
    public function getLiveKey(string $scopeCode = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, int $storeId = null)
    {
        return $this->getConfigByCode(self::CONFIG_LIVE_API_KEY, $scopeCode, $storeId);
    }

    /**
     * @param int | null $storeId
     *
     * @return mixed|null
     */
    public function getTestKey(string $scopeCode = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, int $storeId = null)
    {
        return $this->getConfigByCode(self::CONFIG_TEST_API_KEY, $scopeCode, $storeId);
    }
    /**
     * @return bool
     */
    public function needsAPIKeys(): bool
    {
        return empty(trim($this->getLiveKey())) || empty(trim($this->getTestKey()));
    }

    /**
     * @param int | null $storeId
     *
     * @return mixed|null
     */
    public function getActiveMode(int $storeId = null)
    {
        return $this->getConfigByCode(self::CONFIG_API_MODE);
    }
}
