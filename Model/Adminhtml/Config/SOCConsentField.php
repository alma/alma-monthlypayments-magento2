<?php

namespace Alma\MonthlyPayments\Model\Adminhtml\Config;

use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\SOCHelper;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Class SOCConsentField
 *
 * This class is used to manage data of the Share of Checkout consent in back-office
 */
class SOCConsentField extends Value
{

    /**
     * @var SOCHelper
     */
    private $socHelper;
    /**
     * @var AlmaClient
     */
    private $almaClient;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @param SOCHelper $socHelper
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AlmaClient $almaClient
     * @param Logger $logger
     * @param ManagerInterface $messageManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     */
    public function __construct(
        SOCHelper            $socHelper,
        Context              $context,
        Registry             $registry,
        ScopeConfigInterface $config,
        TypeListInterface    $cacheTypeList,
        AlmaClient           $almaClient,
        Logger               $logger,
        ManagerInterface     $messageManager,
        ?AbstractResource     $resource = null,
        ?AbstractDb           $resourceCollection = null
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, []);
        $this->socHelper = $socHelper;
        $this->almaClient = $almaClient;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
    }

    /**
     * Send consent to API
     *
     * @return SOCConsentField
     */
    public function beforeSave(): SOCConsentField
    {
        if (!$this->isValueChanged()) {
            return $this;
        }
        $this->sendConsent($this->getValue());
        return parent::beforeSave();
    }

    /**
     * Save consent date after save
     *
     * @return $this
     */
    public function afterSave()
    {
        if ($this->isValueChanged() && $this->getValue()) {
            $this->socHelper->saveDate(date('Y-m-d'));
        } elseif ($this->isValueChanged() && !$this->getValue()) {
            $this->socHelper->deleteDate();
        }
        return parent::afterSave();
    }
    /**
     * Change $this->_dataSaveAllowed flag to "false" for disallow save
     *
     * @return void
     */
    protected function disallowDataSave(): void
    {
        $this->_dataSaveAllowed = false;
    }

    /**
     * Send consent to alma API  - No save if an exception is trowed
     *
     * @param string $value
     *
     * @return void
     */
    protected function sendConsent(string $value): void
    {
        try {
            if ($value == SOCHelper::SELECTOR_NO) {
                $this->almaClient->getDefaultClient()->shareOfCheckout->removeConsent();
                return;
            }
            $this->almaClient->getDefaultClient()->shareOfCheckout->addConsent();
        } catch (RequestError | AlmaClientException $e) {
            $this->logger->error('SOC before save exception', [$e]);
            $this->messageManager->addErrorMessage(
                __('Impossible to save merchant data sharing settings, please try again later')
            );
            $this->disallowDataSave();
        }
    }
}
