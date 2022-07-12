<?php

namespace Alma\MonthlyPayments\Helpers\ShareOfCheckout;

use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Alma\MonthlyPayments\Helpers\Logger;
use InvalidArgumentException;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class ShareOfCheckoutHelper extends AbstractHelper
{
    const SHARED_ORDER_STATES = ['processing', 'complete'];
    const SHARE_CHECKOUT_ENABLE_KEY = 'share_checkout_enable';
    const SHARE_CHECKOUT_DATE_KEY = 'share_checkout_date';

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var AlmaClient
     */
    private $almaClient;
    /**
     * @var WriterInterface
     */
    private $configWriter;
    /**
     * @var PayloadBuilder
     */
    private $payloadBuilder;
    /**
     * @var DateHelper
     */
    private $dateHelper;
    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @param Logger $logger
     * @param AlmaClient $almaClient
     * @param Context $context
     * @param WriterInterface $configWriter
     * @param PayloadBuilder $payloadBuilder
     * @param OrderHelper $orderHelper
     * @param DateHelper $dateHelper
     */
    public function __construct(
        Context $context,
        Logger $logger,
        AlmaClient $almaClient,
        WriterInterface $configWriter,
        PayloadBuilder $payloadBuilder,
        OrderHelper $orderHelper,
        DateHelper $dateHelper
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->almaClient = $almaClient;
        $this->configWriter = $configWriter;
        $this->payloadBuilder = $payloadBuilder;
        $this->orderHelper = $orderHelper;
        $this->dateHelper = $dateHelper;
    }

    /**
     * @return bool
     */
    public function shareOfCheckoutIsEnabled(): bool
    {
        return $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . self::SHARE_CHECKOUT_ENABLE_KEY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param string $date
     * @return void
     * @throws RequestError
     */
    public function shareDay(string $date): void
    {
        $res = null;
        $this->checkAlmaClient();
        try {
            $this->dateHelper->setShareDates($date);
            $payload = $this->payloadBuilder->getPayload();
            $this->almaClient->getDefaultClient()->shareOfCheckout->share($payload);
        } catch (RequestError | AlmaClientException $e) {
            $this->logger->error('Share Day request error message', [$e->getMessage()]);
            throw new RequestError($e->getMessage(), null, $res);
        } finally {
            $this->writeLogs();
            $this->orderHelper->flushOrderCollection();
        }
    }

    /**
     * @return string
     * @throws RequestError
     */
    public function getLastUpdateDate(): string
    {
        $this->checkAlmaClient();
        try {
            $lastUpdateByApi = $this->almaClient->getDefaultClient()->shareOfCheckout->getLastUpdateDates();
            return date('Y-m-d', $lastUpdateByApi['end_time']);
        } catch (RequestError | AlmaClientException $e) {
            if ($e->response->responseCode == '404') {
                return date('Y-m-d', strtotime('-1 day'));
            }
            throw new RequestError($e->getMessage(), null);
        }
    }

    /**
     * @return string
     */
    public function getShareOfCheckoutEnabledDate(): string
    {
        $shareOfCheckoutEnabledDate = $this->scopeConfig->getValue(
            $this->getShareOfCheckoutDateKey(),
            ScopeInterface::SCOPE_STORE
        );
        if (empty($shareOfCheckoutEnabledDate)) {
            $this->logger->info('Share of checkout feature was never activated', []);
            throw new InvalidArgumentException('Share of checkout feature was never activated');
        }
        return $shareOfCheckoutEnabledDate;
    }

    /**
     * @return void
     */
    private function writeLogs(): void
    {
        $this->logger->info('Share start date', [$this->dateHelper->getStartDate()]);
        $this->logger->info('Share End date', [$this->dateHelper->getEndDate()]);
    }

    /**
     * @param string $date
     * @return void
     */
    public function saveShareOfCheckoutDate(string $date): void
    {
        $this->configWriter->save($this->getShareOfCheckoutDateKey(), $date);
    }

    /**
     * @return void
     */
    public function deleteShareOfCheckoutDate(): void
    {
        $this->configWriter->delete($this->getShareOfCheckoutDateKey());
    }

    /**
     * @return string
     */
    private function getShareOfCheckoutDateKey(): string
    {
        return ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . self::SHARE_CHECKOUT_DATE_KEY;
    }

    /**
     * @return void
     */
    private function checkAlmaClient(): void
    {
        if (!$this->almaClient) {
            $errorMessage = 'Share of checkout - Alma client is not defined';
            $this->logger->error('checkAlmaClient', [$errorMessage]);
            throw new InvalidArgumentException($errorMessage);
        }
    }
}
