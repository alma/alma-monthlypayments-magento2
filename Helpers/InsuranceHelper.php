<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Entities\Insurance\Subscriber;
use Alma\API\Entities\Insurance\Subscription;
use Alma\API\Exceptions\AlmaException;
use Alma\MonthlyPayments\Model\Data\InsuranceConfig;
use Alma\MonthlyPayments\Model\Data\InsuranceProduct;
use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceProductException;
use Alma\MonthlyPayments\Model\Insurance\SubscriptionFactory;
use InvalidArgumentException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Item\Collection;
use Magento\Store\Model\StoreManagerInterface;

class InsuranceHelper extends AbstractHelper
{
    const ALMA_INSURANCE_SKU = 'alma_insurance';
    const ALMA_INSURANCE_DB_KEY = 'alma_insurance';
    const ALMA_PRODUCT_WITH_INSURANCE_TYPE = 'product_with_alma_insurance';
    const ALMA_INSURANCE_CONFIG_CODE = 'insurance_config';
    const CONFIG_IFRAME_URL = '/almaBackOfficeConfiguration.html';
    const ORDER_DETAIL_IFRAME_URL = '/almaBackOfficeSubscriptions.html';
    const SANDBOX_IFRAME_HOST_URL = 'https://protect.sandbox.almapay.com';
    const PRODUCTION_IFRAME_HOST_URL = 'https://protect.almapay.com';
    const SCRIPT_IFRAME_PATH = '/displayModal.js';
    const FRONT_IFRAME_PATH = '/almaProductInPageWidget.html';
    const MERCHANT_ID_PARAM_KEY = 'merchant_id';
    const CMS_REF_PARAM_KEY = 'cms_reference';
    const PRODUCT_PRICE_PARAM_KEY = 'product_price';
    const CUSTOMER_SESSION_ID_PARAM_KEY = 'customer_session_id';
    const CUSTOMER_CART_ID_PARAM_KEY = 'cart_id';
    const IS_ALLOWED_INSURANCE_PATH = 'insurance_allowed';

    const CALLBACK_URI = '/rest/V1/alma/insurance/update?subscription_id=<subscription_id>&trace=<trace>';

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
     * @var Session
     */
    private $session;
    /**
     * @var SubscriptionFactory
     */
    private $subscriptionFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param ProductRepository $productRepository
     * @param Logger $logger
     * @param Json $json
     * @param ConfigHelper $configHelper
     * @param CartRepositoryInterface $cartRepository
     * @param AlmaClient $almaClient
     * @param Session $session
     */
    public function __construct(
        Context                 $context,
        RequestInterface        $request,
        ProductRepository       $productRepository,
        Logger                  $logger,
        Json                    $json,
        ConfigHelper            $configHelper,
        CartRepositoryInterface $cartRepository,
        AlmaClient              $almaClient,
        SubscriptionFactory     $subscriptionFactory,
        Session                 $session,
        StoreManagerInterface   $storeManager
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
        $this->session = $session;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->storeManager = $storeManager;
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
     * @param float $addItemPrice
     * @param ProductInterface $addedItemToQuote
     * @param string $insuranceId
     * @param string|null $quoteId
     * @return InsuranceProduct|null
     */
    public function getInsuranceProduct(float $addItemPrice, ProductInterface $addedItemToQuote, string $insuranceId, ?string $quoteId = null): ?InsuranceProduct
    {
        $parentSku = $addedItemToQuote->getSku();
        try {
            $insuranceContract = $this->almaClient->getDefaultClient()->insurance->getInsuranceContract(
                $insuranceId,
                $parentSku,
                Functions::priceToCents($addItemPrice),
                $this->session->getSessionId(),
                $quoteId
            );
        } catch (AlmaException $e) {
            $this->logger->error('Get insurance Exception', [$e, $e->getMessage()]);
            return null;
        }

        return new InsuranceProduct($insuranceContract, $parentSku, $addedItemToQuote->getName(), $addItemPrice);
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
        return hash('sha256', $productId . time() . $insuranceId);
    }

    /**
     * @return string
     */
    public function getConfigIframeUrl($mode): string
    {
        $baseUrl = $this->getBaseUrl($mode);
        return $baseUrl . self::CONFIG_IFRAME_URL;
    }

    /**
     * @param string $mode
     * @return string
     */
    public function getScriptUrl(string $mode): string
    {
        $baseUrl = $this->getBaseUrl($mode);
        return $baseUrl . self::SCRIPT_IFRAME_PATH;
    }

    /**
     * @param string $mode
     * @return string
     */
    public function getOrderDetailsUrl(string $mode): string
    {
        $baseUrl = $this->getBaseUrl($mode);
        return $baseUrl . self::ORDER_DETAIL_IFRAME_URL;
    }

    /**
     * By default, insurance product is added before product in cart - reorder to have insurance product after product
     *
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
            array_key_exists(1, $streetArray) ? $streetArray[1] : '',
            $billingAddress->getPostcode(),
            $billingAddress->getCity(),
            $billingAddress->getCountryId()
        );
    }

    /**
     * @param Collection $itemsCollection
     * @param Subscriber $subscriber
     * @return Subscription[]
     */
    public function getSubscriptionData(Collection $itemsCollection, Subscriber $subscriber): array
    {
        $subscriptionArray = [];
        /** @var \Magento\Sales\Model\Order\Invoice\Item $item */
        foreach ($itemsCollection as $item) {
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem = $item->getOrderItem();
            $orderItemQty = (int)$item->getQty();
            $insuranceData = $orderItem->getData(InsuranceHelper::ALMA_INSURANCE_DB_KEY);
            if (!$insuranceData || $item->getSku() !== InsuranceHelper::ALMA_INSURANCE_SKU) {
                continue;
            }
            $insuranceData = json_decode($insuranceData, true);
            for ($i = 0; $i < $orderItemQty; $i++) {
                try {
                    $subscriptionArray[] = new Subscription(
                        $insuranceData['id'],
                        $insuranceData['price'],
                        $insuranceData['parent_sku'],
                        Functions::priceToCents($orderItem->getOriginalPrice()),
                        $subscriber,
                        $this->getCallbackUrl()
                    );
                } catch (\Exception $e) {
                    $this->logger->info('Impossible to create subscription Data : ', ['exception message :' => $e->getMessage(), 'Data' => $insuranceData]);
                }
            }


        }
        return $subscriptionArray;
    }

    /**
     * @param Collection $itemsCollection
     * @param array $subscriptionResult
     * @param int $orderId // same for all subscription
     * @param string $mode // same for all subscription
     * @return \Alma\MonthlyPayments\Model\Insurance\Subscription[]
     * @throws NoSuchEntityException
     */
    public function createDbSubscriptionArrayFromItemsAndApiResult(Collection $itemsCollection, array $subscriptionResult, string $mode): array
    {
        $dbSubscriptionArray = [];
        /** @var \Magento\Sales\Model\Order\Invoice\Item $item */
        foreach ($itemsCollection as $item) {
            if (self::ALMA_INSURANCE_SKU !== $item->getSku()) {
                continue;
            }

            /** @var \Alma\MonthlyPayments\Model\Insurance\Subscription $dbSubscription */
            $orderItem = $item->getOrderItem();
            $orderItemQty = (int)$item->getQty();
            $orderItemInsuranceData = json_decode($orderItem->getData(self::ALMA_INSURANCE_DB_KEY), true);
            $subscriptionResultContractData = [];
            for ($i = 0; $i < $orderItemQty; $i++) {
                foreach ($subscriptionResult as $key => $result) {
                    if (array_search($orderItemInsuranceData['id'], $result)) {
                        $subscriptionResultContractData = $result;
                        unset($subscriptionResult[$key]);
                        break;
                    }
                }
                $dbSubscription = $this->subscriptionFactory->create();
                $dbSubscription->setOrderId($orderItem->getOrderId());
                $dbSubscription->setOrderItemId($orderItem->getItemId());
                $dbSubscription->setName($orderItemInsuranceData['name']);
                $dbSubscription->setSubscriptionId($subscriptionResultContractData['id']);
                $dbSubscription->setSubscriptionAmount(intval($subscriptionResultContractData['amount']));
                $dbSubscription->setContractId($orderItemInsuranceData['id']);
                $dbSubscription->setCmsReference($subscriptionResultContractData['cms_reference']);
                $dbSubscription->setLinkedProductName($orderItemInsuranceData['parent_name']);
                $dbSubscription->setLinkedProductPrice($orderItemInsuranceData['parent_price']);
                $dbSubscription->setSubscriptionState($subscriptionResultContractData['state']);
                $dbSubscription->setSubscriptionMode($mode);
                $dbSubscription->setCallbackUrl($this->getCallbackUrl());
                $dbSubscriptionArray[] = clone $dbSubscription;
            }
        }
        return $dbSubscriptionArray;
    }

    /**
     * @param $mode
     * @return string
     */
    private function getBaseUrl($mode): string
    {
        switch ($mode) {
            case 'test':
                $baseUrl = self::SANDBOX_IFRAME_HOST_URL;
                break;
            case 'live':
                $baseUrl = self::PRODUCTION_IFRAME_HOST_URL;
                break;
            default:
                $baseUrl = self::SANDBOX_IFRAME_HOST_URL;
                $this->logger->info('Unknown mode use sandbox', [$mode]);
                break;
        }
        return $baseUrl;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     * @throws InvalidArgumentException
     */
    private function getCallbackUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl() . self::CALLBACK_URI;
    }

}
