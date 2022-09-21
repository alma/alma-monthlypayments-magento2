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

class SOCHelper extends AbstractHelper
{
    const SHARED_ORDER_STATES = ['processing', 'complete'];
    const SHARE_CHECKOUT_ENABLE_KEY = 'share_checkout_enabled';
    const SHARE_CHECKOUT_DATE_KEY = 'share_checkout_date';
    const SELECTOR_NO =  0;
    const SELECTOR_YES =  1;
    const SELECTOR_NOT_SET =  2;

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
     * Get SOC selector value ( 2 : not set - 0 : no - 1 yes )
     * @return int
     */
    public function getSelectorValue(): int
    {
        return intval($this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . self::SHARE_CHECKOUT_ENABLE_KEY,
            ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * Change SOC selector value into bool
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->getSelectorValue() === self::SELECTOR_YES;
    }

    /**
     * @param string $date
     * @return void
     * @throws RequestError
     */
    public function shareDay(string $date): void
    {
        $res = null;
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
    public function getEnabledDate(): string
    {
        $enabledDate = $this->scopeConfig->getValue(
            $this->getDateKey(),
            ScopeInterface::SCOPE_STORE
        );
        if (empty($enabledDate)) {
            $this->logger->info('Share of checkout feature was never activated', []);
            throw new InvalidArgumentException('Share of checkout feature was never activated');
        }
        return $enabledDate;
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
    public function saveDate(string $date): void
    {
        $this->configWriter->save($this->getDateKey(), $date);
    }

    /**
     * @return void
     */
    public function deleteDate(): void
    {
        $this->configWriter->delete($this->getDateKey());
    }

    /**
     * @return string
     */
    private function getDateKey(): string
    {
        return ConfigHelper::XML_PATH_PAYMENT . '/' . ConfigHelper::XML_PATH_METHODE . '/' . self::SHARE_CHECKOUT_DATE_KEY;
    }
}
