<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Client;

class ApiConfigHelper extends ConfigHelper
{
    const CONFIG_LIVE_API_KEY = 'live_api_key';
    const CONFIG_TEST_API_KEY = 'test_api_key';
    const CONFIG_API_MODE = 'api_mode';
    const TEST_MODE_KEY = 'test';
    const LIVE_MODE_KEY = 'live';

    /**
     * @param string|null $scope
     * @param string|null $storeId
     *
     * @return string
     */
    public function getActiveAPIKey(?string $scope = null, ?string $storeId = null): string
    {
        $mode = $this->getActiveMode($scope, $storeId);
        $apiKeyType = ($mode == Client::LIVE_MODE) ?
            self::CONFIG_LIVE_API_KEY :
            self::CONFIG_TEST_API_KEY;
        return $this->getConfigByCode($apiKeyType, $scope, $storeId);
    }

    /**
     *
     * @return string
     */
    public function getLiveKey(): string
    {
        return $this->getConfigByCode(self::CONFIG_LIVE_API_KEY);
    }

    /**
     *
     * @return string
     */
    public function getTestKey(): string
    {
        return $this->getConfigByCode(self::CONFIG_TEST_API_KEY);
    }

    /**
     * Check if a key is saved
     *
     * @return bool
     */
    public function hasKey(): bool
    {
        return (!empty($this->getTestKey()) || !empty($this->getLiveKey()));
    }

    /**
     * @return bool
     */
    public function needsAPIKeys(): bool
    {
        return empty(trim($this->getLiveKey())) || empty(trim($this->getTestKey()));
    }

    /**
     * @param string|null $scope
     * @param string|null $storeId
     * @return string
     */
    public function getActiveMode(?string $scope = null, ?string $storeId = null): string
    {
        return $this->getConfigByCode(self::CONFIG_API_MODE, $scope, $storeId);
    }
}
