<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Exceptions\AlmaException;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;

class CollectCmsConfigHelper extends ConfigHelper
{
    // Path to the configuration that stores the last time we sent the collect URL to Alma in system.xml
    const SEND_COLLECT_URL_STATUS_PATH = 'send_collect_url_status';

    const COLLECT_URL = 'V1/alma/config/collect';

    private $almaClient;
    private $logger;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param Context $context
     * @param StoreHelper $storeHelper
     * @param WriterInterface $writerInterface
     * @param SerializerInterface $serializer
     * @param TypeListInterface $typeList
     * @param AlmaClient $almaClient
     * @param UrlInterface $urlBuilder
     * @param Logger $logger
     */
    public function __construct(
        Context             $context,
        StoreHelper         $storeHelper,
        WriterInterface     $writerInterface,
        SerializerInterface $serializer,
        TypeListInterface   $typeList,
        AlmaClient          $almaClient,
        UrlInterface        $urlBuilder,
        Logger              $logger
    )
    {
        parent::__construct($context, $storeHelper, $writerInterface, $serializer, $typeList);
        $this->almaClient = $almaClient;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
    }

    /**
     * Send_collect_url_status getter
     *
     * @return string|null
     */
    public function getSendCollectUrlStatus(): ?string
    {
        return $this->getConfigByCode(self::SEND_COLLECT_URL_STATUS_PATH);
    }

    /**
     * Send url to Alma
     *
     * @return void
     */
    public function sendIntegrationsConfigurationsUrl(): void
    {
        try {
            $this->almaClient->getDefaultClient()->merchants->sendIntegrationsConfigurationsUrl($this->urlBuilder->getBaseUrl() . self::COLLECT_URL);
            $this->setSendCollectUrlStatus();
        } catch (AlmaClientException $e) {
            // No need to log this, it's already logged in AlmaClient
        } catch (AlmaException $e) {
            $this->logger->warning('Error while sending integrations configurations URL to Alma', ['exception' => $e]);
        }
    }

    /**
     * Send_collect_url_status setter
     *
     * @return void
     */
    private function setSendCollectUrlStatus(): void
    {
        $this->saveConfig(self::SEND_COLLECT_URL_STATUS_PATH, time(), "default", 0);
    }
}
