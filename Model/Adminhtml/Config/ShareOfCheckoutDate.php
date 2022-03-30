<?php

namespace Alma\MonthlyPayments\Model\Adminhtml\Config;

use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\App\Config\Storage\WriterInterface;

class ShareOfCheckoutDate extends \Magento\Framework\App\Config\Value
{

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var WriterInterface
     */
    private $configWriter;

    public function __construct
    (
        Logger $logger,
        ConfigHelper $configHelper,
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        WriterInterface $configWriter,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->logger = $logger;
        $this->configHelper = $configHelper;
        $this->configWriter = $configWriter;
    }

    public function beforeSave()
    {
        return parent::beforeSave();
    }

    public function afterSave()
    {
        if ($this->isValueChanged() && $this->getValue()){
            $this->logger->info('Set now',[]);
            $this->configHelper->saveShareOfCheckoutDate(date('Y-m-d'));
        }elseif ($this->isValueChanged() && !$this->getValue()) {
            $this->logger->info('Delete Value',[]);
            $this->configHelper->deleteShareOfCheckoutDate();
        } else {
            $this->logger->info('no change',[]);
        }
        return parent::afterSave();
    }

}
