<?php

namespace Alma\MonthlyPayments\Model\Data;

use Alma\API\Entities\MerchantData\CmsFeatures;
use Alma\API\Entities\MerchantData\CmsInfo;
use Alma\API\Lib\PayloadFormatter;
use Alma\API\Lib\RequestUtils;
use Alma\MonthlyPayments\Api\Data\CollectDataInterface;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\CmsFeaturesDataHelper;
use Alma\MonthlyPayments\Helpers\CmsInfoDataHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Exceptions\CollectDataException;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Rest\Response;

class CollectData implements CollectDataInterface
{
    private $payloadFormatter;
    private $cmsInfoDataHelper;
    private $cmsFeaturesDataHelper;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;

    /**
     * @var string
     */
    const HEADER_SIGNATURE_KEY = 'X-Alma-Signature';
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param Logger $logger
     * @param PayloadFormatter $payloadFormatter
     * @param CmsInfoDataHelper $cmsInfoDataHelper
     * @param CmsFeaturesDataHelper $cmsFeaturesDataHelper
     * @param Request $request
     * @param ApiConfigHelper $apiConfigHelper
     * @param Config $config
     * @param Response $response
     */
    public function __construct(
        Logger                $logger,
        PayloadFormatter      $payloadFormatter,
        CmsInfoDataHelper     $cmsInfoDataHelper,
        CmsFeaturesDataHelper $cmsFeaturesDataHelper,
        Request               $request,
        ApiConfigHelper       $apiConfigHelper,
        Config                $config,
        Response              $response
    ) {
        $this->payloadFormatter = $payloadFormatter;
        $this->cmsInfoDataHelper = $cmsInfoDataHelper;
        $this->cmsFeaturesDataHelper = $cmsFeaturesDataHelper;
        $this->request = $request;
        $this->logger = $logger;
        $this->apiConfigHelper = $apiConfigHelper;
        $this->config = $config;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function collect()
    {
        try {
            $this->checkSignature($this->config->getMerchantId(), $this->getApiKey());
        } catch (CollectDataException $e) {
            throw new Exception(__($e->getMessage()), 0, Exception::HTTP_FORBIDDEN);
        }
        $cmsInfo = new CmsInfo($this->cmsInfoDataHelper->getCmsInfoData());
        $cmsFeatures = new CmsFeatures($this->cmsFeaturesDataHelper->getCmsFeaturesData());
        $this->response
            ->setHeader('Content-Type', 'application/json', true)
            ->setBody(json_encode($this->payloadFormatter->formatConfigurationPayload($cmsInfo, $cmsFeatures)))
            ->sendResponse();
    }

    /**
     * @param string | null $merchant_id
     * @param string $apiKey
     * @return void
     * @throws CollectDataException
     */
    private function checkSignature(?string $merchant_id, string $apiKey): void
    {
        $signature = $this->request->getHeader(self::HEADER_SIGNATURE_KEY);
        if (!$signature) {
            throw new CollectDataException("Missing signature");
        }

        if (!RequestUtils::isHmacValidated($merchant_id, $apiKey, $signature)) {
            $this->logger->error("Wrong signature in collect request", [
                '$merchant_id' => $merchant_id,
                'signature' => $signature
            ]);
            throw new CollectDataException("Wrong signature in collect request");
        }
    }

    private function getApiKey(): string
    {
        $apiKey = $this->apiConfigHelper->getActiveAPIKey();
        if (!$apiKey) {
            throw new CollectDataException("Missing API key in collect request");
        }
        return $apiKey;
    }
}
