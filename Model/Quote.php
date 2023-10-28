<?php

namespace Alma\MonthlyPayments\Model;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Directory\Model\AllowedCountries;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Quote\Model\Quote as BaseQuote;
use Magento\Quote\Model\QuoteValidator;
use Magento\Quote\Model\ShippingAssignmentFactory;
use Magento\Quote\Model\ShippingFactory;

class Quote extends BaseQuote
{

    /**
     * Quote customer model object
     *
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customer;

    /**
     * Quote addresses collection
     *
     * @var \Magento\Eav\Model\Entity\Collection\AbstractCollection
     */
    protected $_addresses;

    /**
     * Quote items collection
     *
     * @var \Magento\Eav\Model\Entity\Collection\AbstractCollection
     */
    protected $_items;

    /**
     * Quote payments
     *
     * @var \Magento\Eav\Model\Entity\Collection\AbstractCollection
     */
    protected $_payments;

    /**
     * @var \Magento\Quote\Model\Quote\Payment
     */
    protected $_currentPayment;

    /**
     * Different groups of error infos
     *
     * @var array
     */
    protected $_errorInfoGroups = [];

    /**
     * Whether quote should not be saved
     *
     * @var bool
     */
    protected $_preventSaving = false;

    /**
     * Product of the catalog
     *
     * @var \Magento\Catalog\Helper\Product
     */
    protected $_catalogProduct;

    /**
     * To perform validation on the quote
     *
     * @var \Magento\Quote\Model\QuoteValidator
     */
    protected $quoteValidator;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_config;

    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    protected $_quoteAddressFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * Repository for group to perform CRUD operations
     *
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory
     */
    protected $_quoteItemCollectionFactory;

    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $_quoteItemFactory;

    /**
     * @var \Magento\Framework\Message\Factory
     */
    protected $messageFactory;

    /**
     * @var \Magento\Sales\Model\Status\ListFactory
     */
    protected $_statusListFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Quote\Model\Quote\PaymentFactory
     */
    protected $_quotePaymentFactory;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Payment\CollectionFactory
     */
    protected $_quotePaymentCollectionFactory;

    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    protected $_objectCopyService;

    /**
     * Repository for customer address to perform crud operations
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * It is used for building search criteria
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * This is used for holding  builder object for filter service
     *
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Magento\Quote\Model\Quote\Item\Processor
     */
    protected $itemProcessor;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $objectFactory;

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory
     */
    protected $addressDataFactory;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    protected $customerDataFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Cart\CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var \Magento\Quote\Model\Quote\TotalsCollector
     */
    protected $totalsCollector;

    /**
     * @var \Magento\Quote\Model\Quote\TotalsReader
     */
    protected $totalsReader;

    /**
     * @var \Magento\Quote\Model\ShippingFactory
     */
    protected $shippingFactory;

    /**
     * @var \Magento\Quote\Model\ShippingAssignmentFactory
     */
    protected $shippingAssignmentFactory;

    /**
     * Quote shipping addresses items cache
     *
     * @var array
     */
    protected $shippingAddressesItems;

    /**
     * @var \Magento\Sales\Model\OrderIncrementIdChecker
     */
    private $orderIncrementIdChecker;

    /**
     * @var AllowedCountries
     */
    private $allowedCountriesReader;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var InsuranceHelper
     */
    private $insuranceHelper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param QuoteValidator $quoteValidator
     * @param \Magento\Catalog\Helper\Product $catalogProduct
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param Quote\AddressFactory $quoteAddressFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollectionFactory
     * @param Quote\ItemFactory $quoteItemFactory
     * @param \Magento\Framework\Message\Factory $messageFactory
     * @param Status\ListFactory $statusListFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param Quote\PaymentFactory $quotePaymentFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote\Payment\CollectionFactory $quotePaymentCollectionFactory
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param Quote\Item\Processor $itemProcessor
     * @param \Magento\Framework\DataObject\Factory $objectFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param Cart\CurrencyFactory $currencyFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param Quote\TotalsCollector $totalsCollector
     * @param Quote\TotalsReader $totalsReader
     * @param ShippingFactory $shippingFactory
     * @param ShippingAssignmentFactory $shippingAssignmentFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param \Magento\Sales\Model\OrderIncrementIdChecker|null $orderIncrementIdChecker
     * @param AllowedCountries|null $allowedCountriesReader
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        \Magento\Catalog\Helper\Product $catalogProduct,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollectionFactory,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\Framework\Message\Factory $messageFactory,
        \Magento\Sales\Model\Status\ListFactory $statusListFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Quote\Model\Quote\PaymentFactory $quotePaymentFactory,
        \Magento\Quote\Model\ResourceModel\Quote\Payment\CollectionFactory $quotePaymentCollectionFactory,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Quote\Model\Quote\Item\Processor $itemProcessor,
        \Magento\Framework\DataObject\Factory $objectFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Magento\Quote\Model\Cart\CurrencyFactory $currencyFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        BaseQuote\TotalsCollector $totalsCollector,
        BaseQuote\TotalsReader $totalsReader,
        \Magento\Quote\Model\ShippingFactory $shippingFactory,
        \Magento\Quote\Model\ShippingAssignmentFactory $shippingAssignmentFactory,
        Logger $logger,
        InsuranceHelper $insuranceHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        \Magento\Sales\Model\OrderIncrementIdChecker $orderIncrementIdChecker = null,
        AllowedCountries $allowedCountriesReader = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $quoteValidator,
            $catalogProduct,
            $scopeConfig,
            $storeManager,
            $config,
            $quoteAddressFactory,
            $customerFactory,
            $groupRepository,
            $quoteItemCollectionFactory,
            $quoteItemFactory,
            $messageFactory,
            $statusListFactory,
            $productRepository,
            $quotePaymentFactory,
            $quotePaymentCollectionFactory,
            $objectCopyService,
            $stockRegistry,
            $itemProcessor,
            $objectFactory,
            $addressRepository,
            $criteriaBuilder,
            $filterBuilder,
            $addressDataFactory,
            $customerDataFactory,
            $customerRepository,
            $dataObjectHelper,
            $extensibleDataObjectConverter,
            $currencyFactory,
            $extensionAttributesJoinProcessor,
            $totalsCollector,
            $totalsReader,
            $shippingFactory,
            $shippingAssignmentFactory,
            $resource = null,
            $resourceCollection = null,
            $data = [],
            $orderIncrementIdChecker = null,
            $allowedCountriesReader = null
        );
        $this->logger = $logger;
        $this->insuranceHelper = $insuranceHelper;
    }
    public function getItemByProduct($product)
    {
        $this->logger->info('new Get  Items by product', []);
        $almaInsuranceProduct = $this->insuranceHelper->getAlmaInsuranceProduct();
        $almaProductInRequest = $this->insuranceHelper->getInsuranceParamsInRequest();

        if ($almaProductInRequest) {
            $this->logger->info('It s a new product with an insurance',[$almaProductInRequest->getName()]);
            return false;
        }


        if ($product->getId() === $almaInsuranceProduct->getId()) {
            $this->logger->info('It s an insurance product don t stack', []);
            return false;
        }

        /** @var \Magento\Quote\Model\Quote\Item[] $items */
        $items = $this->getItemsCollection()->getItemsByColumnValue('product_id', $product->getId());
        foreach ($items as $item) {
            if (!$item->isDeleted()
                && $item->getProduct()
                && $item->getProduct()->getStatus() !== ProductStatus::STATUS_DISABLED
                && $item->representProduct($product)
            ) {
                if ($this->insuranceHelper->getQuoteItemAlmaInsurance($item)){
                    $this->logger->info('It an item with insurance continue',[]);

                    continue;
                }
                return $item;
            }
        }
        return false;
    }
}
