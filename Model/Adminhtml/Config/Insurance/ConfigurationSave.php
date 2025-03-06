<?php

namespace Alma\MonthlyPayments\Model\Adminhtml\Config\Insurance;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\InsuranceProductHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\QuoteHelper;
use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceProductException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class ConfigurationSave extends Value
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var InsuranceProductHelper
     */
    private $insuranceProductHelper;
    /**
     * @var QuoteHelper
     */
    private $quoteHelper;

    public function __construct(
        Context                    $context,
        Registry                   $registry,
        ScopeConfigInterface       $config,
        TypeListInterface          $cacheTypeList,
        Logger                     $logger,
        InsuranceProductHelper     $insuranceProductHelper,
        QuoteHelper                $quoteHelper,
        AbstractResource           $resource = null,
        AbstractDb                 $resourceCollection = null,
        array                      $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->logger = $logger;
        $this->insuranceProductHelper = $insuranceProductHelper;
        $this->quoteHelper = $quoteHelper;
    }

    /**
     * @return $this
     * @throws AlmaInsuranceProductException
     */
    public function beforeSave(): ConfigurationSave
    {
        $arrayValue = json_decode($this->getValue(), true);
        if (!$this->isValueChanged()) {
            return $this;
        }
        if (!isset($arrayValue['isInsuranceActivated']) ||
            $arrayValue['isInsuranceActivated'] === false
        ) {
            $this->insuranceProductHelper->disableInsuranceProductIfExist();
            $this->quoteHelper->deleteInsuranceDataFromQuoteItemForNotConvertedQuote();
            return $this;
        }

        try {
            $this->insuranceProductHelper->enableInsuranceProductIfExist();
            return $this;
        } catch (AlmaInsuranceProductException $e) {
            $this->logger->info('Insurance product not found, creating it');
        }

        $this->insuranceProductHelper->createInsuranceProduct();
        return $this;
    }
}
