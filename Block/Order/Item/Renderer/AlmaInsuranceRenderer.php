<?php

namespace Alma\MonthlyPayments\Block\Order\Item\Renderer;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;
use Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer;
use Magento\Sales\Model\Order\Item;

class AlmaInsuranceRenderer extends DefaultRenderer
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var CollectionFactory
     */
    private $quoteItemCollectionFactory;
    /**
     * @var InsuranceHelper
     */
    private $insuranceHelper;


    public function __construct(
        Context           $context,
        StringUtils       $string,
        OptionFactory     $productOptionFactory,
        Logger            $logger,
        CollectionFactory $quoteCollectionFactory,
        InsuranceHelper   $insuranceHelper,
        array             $data = []
    ) {
        parent::__construct(
            $context,
            $string,
            $productOptionFactory,
            $data
        );
        $this->logger = $logger;

        $this->quoteItemCollectionFactory = $quoteCollectionFactory;
        $this->insuranceHelper = $insuranceHelper;
    }

    /**
     * Get item.
     *
     * @return array|null
     */
    public function getItem(): ?array
    {

        /** @var Item $item */
        $item = parent::getItem();

        /** @var \Magento\Quote\Model\Quote\Item $quotItem */

        if (InsuranceHelper::ALMA_INSURANCE_SKU === $item->getSku()) {
            $quoteItemCollection = $this->quoteItemCollectionFactory->create();
            $quoteItem = $quoteItemCollection
                ->addFieldToSelect('*')
                ->addFieldToFilter('item_id', [$item->getQuoteItemId()])
                ->getFirstItem();

            $item->setName($this->insuranceHelper->getInsuranceName($quoteItem));

        }
        return parent::getItem();
    }
}
