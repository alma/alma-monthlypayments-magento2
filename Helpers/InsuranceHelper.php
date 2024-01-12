<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Entities\Insurance\Subscriber;
use Alma\API\Entities\Insurance\Subscription;
use Alma\API\Exceptions\AlmaException;
use Alma\MonthlyPayments\Model\Data\InsuranceConfig;
use Alma\MonthlyPayments\Model\Data\InsuranceProduct;
use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceProductException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Item\Collection;

class InsuranceHelper extends AbstractHelper
{
    const ALMA_INSURANCE_SKU = 'alma_insurance';
    const ALMA_INSURANCE_DB_KEY = 'alma_insurance';
    const ALMA_PRODUCT_WITH_INSURANCE_TYPE = 'product_with_alma_insurance';
    const ALMA_INSURANCE_CONFIG_CODE = 'insurance_config';
    const CONFIG_IFRAME_URL = 'https://protect.staging.almapay.com/almaBackOfficeConfiguration.html';
    //TODO fix with ne final host
    const SANDBOX_IFRAME_HOST_URL = 'https://protect.staging.almapay.com';
    //TODO fix with ne final host
    const PRODUCTION_IFRAME_HOST_URL = 'https://protect.almapay.com';
    const SCRIPT_IFRAME_PATH = '/displayModal.js';
    const CONFIG_IFRAME_PATH = '/almaBackOfficeConfiguration.html';
    const FRONT_IFRAME_PATH = '/almaProductInPageWidget.html';
    const MERCHANT_ID_PARAM_KEY = 'merchant_id';
    const CMS_REF_PARAM_KEY = 'cms_reference';
    const PRODUCT_PRICE_PARAM_KEY = 'product_price';

    const IS_ALLOWED_INSURANCE_PATH = 'insurance_allowed';

    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * @var AlmaClient
     */
    private $almaClient;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param ProductRepository $productRepository
     * @param Logger $logger
     * @param Json $json
     */
    public function __construct(
        Context                 $context,
        RequestInterface        $request,
        ProductRepository       $productRepository,
        Logger                  $logger,
        Json                    $json,
        ConfigHelper            $configHelper,
        CartRepositoryInterface $cartRepository,
        AlmaClient              $almaClient
    )
    {
        parent::__construct($context);
        $this->json = $json;
        $this->request = $request;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->configHelper = $configHelper;
        $this->cartRepository = $cartRepository;
        $this->almaClient = $almaClient;
    }

    /**
     * @return InsuranceConfig
     */
    public function getConfig(): InsuranceConfig
    {
        $isAllowed = (bool)$this->configHelper->getConfigByCode(self::IS_ALLOWED_INSURANCE_PATH);
        $configData = (string)$this->configHelper->getConfigByCode(self::ALMA_INSURANCE_CONFIG_CODE);
        return new InsuranceConfig($isAllowed, $configData);
    }

    /**
     * Get alma_insurance data from model
     *
     * @param Item $quoteItem
     * @return string
     */
    public function getQuoteItemAlmaInsurance(Item $quoteItem): ?string
    {
        return $quoteItem->getAlmaInsurance();
    }

    /**
     * Set alma_insurance in DB
     *
     * @param Item $quoteItem
     * @param array|null $data
     * @param string|null $type
     * @return Item
     */
    public function setAlmaInsuranceToQuoteItem(Item $quoteItem, array $data = null, string $type = null): Item
    {
        if (!$type) {
            $type = self::ALMA_INSURANCE_SKU;
        }
        if ($data) {
            $data['type'] = $type;
            $data = $this->json->serialize($data);
        }
        return $quoteItem->setData(self::ALMA_INSURANCE_SKU, $data);
    }

    /**
     * @param ProductInterface $addedItemToQuote
     * @param string $insuranceId
     * @return InsuranceProduct|null
     */
    public function getInsuranceProduct(ProductInterface $addedItemToQuote, string $insuranceId): ?InsuranceProduct
    {
        $parentName = $addedItemToQuote->getName();
        $parentSku = $addedItemToQuote->getSku();
        $parentRegularPrice = $addedItemToQuote->getPrice();

        try {
            $insuranceContract = $this->almaClient->getDefaultClient()->insurance->getInsuranceContract($insuranceId, $parentSku, Functions::priceToCents($parentRegularPrice));
        } catch (AlmaException $e) {
            $this->logger->error('Get insurance Exception', [$e, $e->getMessage()]);
            return null;
        }

        $this->logger->info('New insurance Product', []);
        return new InsuranceProduct($insuranceContract, $parentName);
    }

    /**
     * @return bool
     */
    public function hasInsuranceInRequest(): bool
    {
        return (bool)$this->request->getParam('alma_insurance_id');
    }

    /**
     * @return Product
     * @throws AlmaInsuranceProductException
     */
    public function getAlmaInsuranceProduct(): Product
    {
        try {
            return $this->productRepository->get(self::ALMA_INSURANCE_SKU);
        } catch (NoSuchEntityException $e) {
            $message = 'No alma Insurance product in Catalog - Use a product with sku : ' . self::ALMA_INSURANCE_SKU;
            $this->logger->error($message, [$e->getMessage()]);
            throw new AlmaInsuranceProductException($message, 0, $e);
        }
    }

    /**
     * @param int $productId
     * @param string $insuranceId
     * @return string
     */
    public function createLinkToken(int $productId, string $insuranceId): string
    {
        return (hash('sha256', $productId . time() . $insuranceId));
    }

    /**
     * @return string
     */
    public function getIframeUrlWithParams(): string
    {
        $configArray = $this->getConfig()->getArrayConfig();
        unset($configArray['is_insurance_activated']);
        $paramNumber = 0;
        $uri = '';
        foreach ($configArray as $key => $value) {
            $uri .= ($paramNumber === 0 ? '?' : '&') . $key . '=' . ($value ? 'true' : 'false');
            $paramNumber++;
        }
        return self::CONFIG_IFRAME_URL . $uri;
    }

    /**
     * @param array $items
     * @return array
     */
    public function reorderMiniCart(array $items): array
    {
        foreach ($items as $key => $item) {
            if ($item['isInsuranceProduct'] && $items[$key + 1]) {
                [$items[$key], $items[$key + 1]] = [$items[$key + 1], $items[$key]];
            }
        }
        return $items;
    }

    /**
     * @param string $linkToken
     * @param array $quoteItems
     * @return Item|null
     */
    public function getInsuranceProductToRemove(string $linkToken, array $quoteItems): ?Item
    {
        /** @var Item $quoteItem */
        foreach ($quoteItems as $quoteItem) {
            if ($quoteItem->getSku() != self::ALMA_INSURANCE_SKU) {
                continue;
            }
            $insuranceData = json_decode($quoteItem->getData(self::ALMA_INSURANCE_SKU), true);
            if ($insuranceData && $linkToken === $insuranceData['link']) {
                return $quoteItem;
            }
        }
        return null;
    }

    /**
     * @param string $linkToken
     * @param array $quoteItems
     * @return Item|null
     */
    public function getProductLinkedToInsurance(string $linkToken, array $quoteItems): ?Item
    {
        /** @var Item $quoteItem */
        foreach ($quoteItems as $quoteItem) {
            if ($quoteItem->getSku() === self::ALMA_INSURANCE_SKU) {
                continue;
            }
            $insuranceData = $quoteItem->getData(self::ALMA_INSURANCE_SKU);
            if (!$insuranceData) {
                continue;
            }
            $insuranceData = json_decode($quoteItem->getData(self::ALMA_INSURANCE_SKU), true);
            if ($insuranceData && $linkToken === $insuranceData['link']) {
                return $quoteItem;
            }
        }
        return null;
    }

    /**
     * @param Item $quoteItem
     * @return void
     */
    public function removeQuoteItemFromCart(Item $quoteItem): void
    {
        $quote = $quoteItem->getQuote();
        $quote->deleteItem($quoteItem);
        $this->cartRepository->save($quote);
    }

    /**
     * @param Item $quoteItem
     * @return string
     */
    public function getInsuranceName(Item $quoteItem): string
    {
        $almaInsurance = json_decode($quoteItem->getData('alma_insurance'), true);
        return $almaInsurance['name'];
    }

    /**
     * @param Address $billingAddress
     * @return Subscriber
     */
    public function getSubscriberByAddress(Address $billingAddress): Subscriber
    {
        $streetArray = $billingAddress->getStreet();
        return new Subscriber(
            $billingAddress->getEmail(),
            $billingAddress->getTelephone(),
            $billingAddress->getLastname(),
            $billingAddress->getFirstname(),
            array_key_exists(0, $streetArray) ? $streetArray[0] : '',
            array_key_exists(0, $streetArray) ? $streetArray[1] : '',
            $billingAddress->getPostcode(),
            $billingAddress->getCity(),
            $billingAddress->getCountryId()
        );

    }

    /**
     * @param Collection $itemsCollection
     * @param Subscriber $subscriber
     * @return array
     */
    public function getSubscriptionData(Collection $itemsCollection, Subscriber $subscriber): array
    {
        $subscriptionArray = [];
        /** @var \Magento\Sales\Model\Order\Invoice\Item $item */
        foreach ($itemsCollection as $item) {
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem = $item->getOrderItem();
            $insuranceData = $orderItem->getData(InsuranceHelper::ALMA_INSURANCE_DB_KEY);
            if (!$insuranceData || $item->getSku() === InsuranceHelper::ALMA_INSURANCE_SKU) {
                continue;
            }
            $insuranceData = json_decode($insuranceData, true);
            $subscriptionArray[] = new Subscription(
                $insuranceData['id'],
                $item->getSku(),
                Functions::priceToCents($orderItem->getOriginalPrice()),
                $subscriber
            );
        }
        return $subscriptionArray;
    }
}
