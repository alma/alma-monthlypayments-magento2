<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Client;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;

class ApiConfigHelper extends ConfigHelper
{
    const CONFIG_LIVE_API_KEY = 'live_api_key';
    const CONFIG_TEST_API_KEY = 'test_api_key';
    const CONFIG_API_MODE = 'api_mode';
    /**
     * @var Logger
     */
    private $logger;


    public function __construct(
        Logger $logger,
        Context $context,
        StoreHelper $storeHelper,
        WriterInterface $writerInterface
    ) {
        parent::__construct($context, $storeHelper, $writerInterface);
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function getActiveAPIKey(): string
    {
        $mode = $this->getActiveMode();
        $apiKeyType = ($mode == Client::LIVE_MODE) ?
            self::CONFIG_LIVE_API_KEY :
            self::CONFIG_TEST_API_KEY ;
        return $this->getConfigByCode($apiKeyType);
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
     * @return bool
     */
    public function needsAPIKeys(): bool
    {
        return empty(trim($this->getLiveKey())) || empty(trim($this->getTestKey()));
    }

    /**
     *
     * @return string
     */
    public function getActiveMode(): string
    {
        return $this->getConfigByCode(self::CONFIG_API_MODE);
    }
}
