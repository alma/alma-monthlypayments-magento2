<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Client;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreResolver;

class ApiConfigHelper extends ConfigHelper
{
    const CONFIG_LIVE_API_KEY = 'live_api_key';
    const CONFIG_TEST_API_KEY = 'test_api_key';
    const CONFIG_API_MODE = 'api_mode';
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var StoreResolver
     */
    private $storeResolver;


    public function __construct(
        StoreResolver $storeResolver,
        Logger $logger,
        Context $context,
        RequestInterface $request,
        State $state,
        WriterInterface $writerInterface
    ) {
        parent::__construct($storeResolver, $context, $request, $state, $writerInterface);
        $this->logger = $logger;
        $this->storeResolver = $storeResolver;
    }

    /**
     * @return mixed|null
     */
    public function getActiveAPIKey()
    {
        $storeId = $this->storeResolver->getCurrentStoreId();
        $scope = $this->getScope($storeId);
        $mode = $this->getActiveMode($scope, $storeId);
        $apiKeyType = ($mode == Client::LIVE_MODE) ?
            self::CONFIG_LIVE_API_KEY :
            self::CONFIG_TEST_API_KEY ;
        return $this->getConfigByCode($apiKeyType, $scope, $storeId);
    }

    /**
     * @param int | null $storeId
     *
     * @return string
     */
    public function getLiveKey(string $scopeCode = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, int $storeId = null): string
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
    public function getActiveMode(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, int $storeId = null)
    {
        return $this->getConfigByCode(self::CONFIG_API_MODE, $scope, $storeId);
    }
}
