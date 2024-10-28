<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Exceptions\AlmaException;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\SerializerInterface;

class CollectCmsConfigHelper extends ConfigHelper
{
    // Path to the configuration that stores the last time we sent the collect URL to Alma in system.xml
    const SEND_COLLECT_URL_STATUS_PATH = 'send_collect_url_status';

    const COLLECT_URL = '/V1/alma/config/collect';

    private $almaClient;
    private Logger $logger;

    public function __construct(
        Context             $context,
        StoreHelper         $storeHelper,
        WriterInterface     $writerInterface,
        SerializerInterface $serializer,
        TypeListInterface   $typeList,
        AlmaClient          $almaClient,
        Logger              $logger
    )
    {
        parent::__construct($context, $storeHelper, $writerInterface, $serializer, $typeList);
        $this->almaClient = $almaClient;
        $this->logger = $logger;
    }

    private function getSendCollectUrlStatus(): ?string
    {
        return $this->getConfigByCode(self::SEND_COLLECT_URL_STATUS_PATH);
    }

    private function setSendCollectUrlStatus():void
    {
        $this->saveConfig(self::SEND_COLLECT_URL_STATUS_PATH, time());
    }

    public function sendIntegrationsConfigurationsUrl():void
    {
        try {
            $this->almaClient->getDefaultClient()->merchants->sendIntegrationsConfigurationsUrl(self::COLLECT_URL);
            $this->setSendCollectUrlStatus();
        } catch (AlmaClientException) {
            // No need to log this, it's already logged in AlmaClient
        } catch (AlmaException $e) {
            $this->logger->warning('Error while sending integrations configurations URL to Alma', ['exception' => $e]);
        }
    }

    public function isUrlRefreshRequired(): bool
    {
        $oneMonthInSeconds = 30 * 24 * 60 * 60; // 30 jours en sec
        return (time() - $this->getSendCollectUrlStatus()) > $oneMonthInSeconds;
    }
}
