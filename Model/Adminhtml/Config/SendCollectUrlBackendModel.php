<?php

namespace Alma\MonthlyPayments\Model\Adminhtml\Config;

use Alma\API\Lib\IntegrationsConfigurationsUtils;
use Alma\MonthlyPayments\Helpers\CollectCmsConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class SendCollectUrlBackendModel extends Value
{

    private $logger;
    private $collectCmsConfigHelper;

    /**
     * SendCollectUrlBackendModel construct
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param Logger $logger
     * @param CollectCmsConfigHelper $collectCmsConfigHelper
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context                $context,
        Registry               $registry,
        ScopeConfigInterface   $config,
        TypeListInterface      $cacheTypeList,
        Logger                 $logger,
        CollectCmsConfigHelper $collectCmsConfigHelper,
        AbstractResource       $resource = null,
        AbstractDb             $resourceCollection = null,
        array                  $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
        $this->logger = $logger;
        $this->collectCmsConfigHelper = $collectCmsConfigHelper;
    }

    /**
     * Send the collect URL to Alma if necessary
     *
     * @return SendCollectUrlBackendModel
     */
    public function afterSave()
    {

        if (IntegrationsConfigurationsUtils::isUrlRefreshRequired((int)$this->collectCmsConfigHelper->getSendCollectUrlStatus())) {
            $this->collectCmsConfigHelper->sendIntegrationsConfigurationsUrl();
        }
        return parent::afterSave();
    }

}
