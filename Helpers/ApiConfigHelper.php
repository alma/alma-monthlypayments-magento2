<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Client;

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
     * @return mixed|null
     */
    public function getLiveKey()
    {
        return $this->getConfigByCode(self::CONFIG_LIVE_API_KEY);
    }

    /**
     * @return mixed|null
     */
    public function getTestKey()
    {
        return $this->getConfigByCode(self::CONFIG_TEST_API_KEY);
    }
    /**
     * @return bool
     */
    public function needsAPIKeys(): bool
    {
        return empty(trim($this->getLiveKey())) || empty(trim($this->getTestKey()));
    }
    /**
     * @return bool
     */
    public function isFullyConfigured(): bool
    {
        return !$this->needsAPIKeys() && (bool)(int)$this->getConfigByCode(self::CONFIG_FULLY_CONFIGURED);
    }

    /**
     * @return mixed|null
     */
    public function getActiveMode()
    {
        return $this->getConfigByCode(self::CONFIG_API_MODE);
    }
}
