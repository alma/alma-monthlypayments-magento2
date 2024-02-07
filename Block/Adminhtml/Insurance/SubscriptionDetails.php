<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\Insurance;

use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription\Collection;
use Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription\CollectionFactory;
use Magento\Backend\Block\Template;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;

class SubscriptionDetails extends Template
{
    /**
     * @var InsuranceHelper
     */
    private $insuranceHelper;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Collection
     */
    private $collectionFactory;
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    public function __construct(
        Logger            $logger,
        Template\Context  $context,
        InsuranceHelper   $insuranceHelper,
        ApiConfigHelper   $apiConfigHelper,
        CollectionFactory $collectionFactory,
        OrderRepository   $orderRepository,
        array             $data = [],
        ?JsonHelper       $jsonHelper = null,
        ?DirectoryHelper  $directoryHelper = null
    )
    {
        parent::__construct(
            $context,
            $data,
            $jsonHelper,
            $directoryHelper
        );
        $this->insuranceHelper = $insuranceHelper;
        $this->apiConfigHelper = $apiConfigHelper;
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return string
     */
    public function getScriptUrl(): string
    {
        return 'https://protect.staging.almapay.com/displayModal.js';
        //return $this->insuranceHelper->getScriptUrl($this->apiConfigHelper->getActiveMode());
    }

    /**
     * @return array
     */
    public function getSubscriptionCollection(): array
    {
        $collection = $this->collectionFactory->create();
        $this->logger->info('$collection', [$collection]);

        $collection->addFieldToFilter('order_id', $this->_request->getParam('order_id'));
        $collection->getSelect()->joinLeft(
            ['order' => 'sales_order'],
            'main_table.order_id = order.entity_id',
            ['order.increment_id']
        );
        $this->logger->info('$collection->getData()', [$collection->getData()]);
        return $collection->getData();
    }

    /**
     * @return string
     */
    public function getActiveMode(): string
    {
        return $this->apiConfigHelper->getActiveMode();
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->_request->getParam('order_id');
    }

    /**
     * @return OrderInterface|null
     */
    public function getOrder(): ?OrderInterface
    {
        try {
            return $this->orderRepository->get(intval($this->_request->getParam('order_id')));
        } catch (InputException | NoSuchEntityException $e) {
            $this->logger->error('Impossible to get Order in DB', [$this->_request->getParam('order_id')]);
            return null;
        }
    }

    /**
     * @return string
     */
    public function getIncrementId(): string
    {
        return $this->getOrder()->getIncrementId();
    }

    /**
     * @return string
     */
    public function getOrderDate(): string
    {
        return $this->getOrder()->getCreatedAt();
    }

    /**
     * @return string
     */
    public function getCustomerFirstName(): string
    {
        return $this->getOrder()->getCustomerFirstname();
    }

    /**
     * @return string
     */
    public function getCustomerLastName(): string
    {
        return $this->getOrder()->getCustomerLastname();
    }

}
