<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Client;
use Alma\MonthlyPayments\Gateway\Config\Config;

class ApiConfigHelper extends Config
{
    const CONFIG_LIVE_API_KEY = 'live_api_key';
    const CONFIG_TEST_API_KEY = 'test_api_key';
    const CONFIG_FULLY_CONFIGURED = 'fully_configured';

    /**
     * @return mixed|null
     */
    public function getActiveAPIKey()
    {

        $mode = $this->getActiveMode();
        $apiKeyType = ($mode == Client::LIVE_MODE) ?
            self::CONFIG_LIVE_API_KEY :
            self::CONFIG_TEST_API_KEY ;
        return $this->get($apiKeyType);
    }

    /**
     * @return mixed|null
     */
    public function getLiveKey()
    {
        return $this->get(self::CONFIG_LIVE_API_KEY, '');
    }

    /**
     * @return mixed|null
     */
    public function getTestKey()
    {
        return $this->get(self::CONFIG_TEST_API_KEY, '');
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
        return !$this->needsAPIKeys() && (bool)(int)$this->get(self::CONFIG_FULLY_CONFIGURED, false);
    }

    /**
     * @return mixed|null
     */
    public function getActiveMode()
    {
        return $this->get(self::CONFIG_API_MODE, Client::LIVE_MODE);
    }
}
