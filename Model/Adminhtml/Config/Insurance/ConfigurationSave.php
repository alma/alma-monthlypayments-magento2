<?php

namespace Alma\MonthlyPayments\Model\Adminhtml\Config\Insurance;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\InsuranceProductHelper;
use Alma\MonthlyPayments\Helpers\Logger;
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
    private $productRepository;
    private $insuranceProductHelper;

    public function __construct(
        Context                    $context,
        Registry                   $registry,
        ScopeConfigInterface       $config,
        TypeListInterface          $cacheTypeList,
        Logger                     $logger,
        ProductRepositoryInterface $productRepository,
        InsuranceProductHelper     $insuranceProductHelper,
        AbstractResource           $resource = null,
        AbstractDb                 $resourceCollection = null,
        array                      $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->insuranceProductHelper = $insuranceProductHelper;
    }

    /**
     * @throws AlmaInsuranceProductException
     * @return $this
     */
    public function beforeSave(): ConfigurationSave
    {
        $arrayValue = json_decode($this->getValue(), true);
        if (
            !$this->isValueChanged() ||
            !isset($arrayValue['isInsuranceActivated']) ||
            $arrayValue['isInsuranceActivated'] === false
        ) {
            return $this;
        }

        try {
            $this->productRepository->get(InsuranceHelper::ALMA_INSURANCE_SKU);
            return $this;
        } catch (NoSuchEntityException $e) {
            $this->logger->info('Insurance product not found, creating it');
        }

        $this->insuranceProductHelper->createInsuranceProduct();
        return $this;
    }
}
