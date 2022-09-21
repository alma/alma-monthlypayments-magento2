<?php

namespace Alma\MonthlyPayments\Model\Adminhtml\Config;

use Alma\MonthlyPayments\Helpers\ShareOfCheckout\SOCHelper;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

class SOCDate extends Value
{

    /**
     * @var SOCHelper
     */
    private $socHelper;

    /**
     * @param SOCHelper $socHelper
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     */
    public function __construct(
        SOCHelper            $socHelper,
        Context              $context,
        Registry             $registry,
        ScopeConfigInterface $config,
        TypeListInterface    $cacheTypeList,
        AbstractResource     $resource = null,
        AbstractDb           $resourceCollection = null
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, []);
        $this->socHelper = $socHelper;
    }

    /**
     * @return SOCDate
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

}
