<?php

namespace Alma\MonthlyPayments\Model\Insurance;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class Subscription extends AbstractModel implements IdentityInterface
{
    const ID_KEY = 'entity_id';
    const ORDER_ID_KEY = 'order_id';
    const ORDER_ITEM_ID_KEY = 'order_item_id';
    const SUBSCRIPTION_ID_KEY = 'subscription_id';
    const BROKER_SUBSCRIPTION_ID_KEY = 'broker_subscription_id';
    const SUBSCRIPTION_NAME_KEY = 'name';
    const SUBSCRIPTION_PRICE_KEY = 'subscription_price';
    const CONTRACT_ID_KEY = 'contract_id';
    const CMS_REFERENCE_KEY = 'cms_reference';
    const SUBSCRIPTION_STATE_KEY = 'state';
    const SUBSCRIPTION_MODE_KEY = 'mode';
    const CANCELLATION_DATE_KEY = 'cancellation_date';
    const CANCELLATION_REASON_KEY = 'cancellation_reason';
    const IS_REFUND_KEY = 'is_refunded';
    const CALLBACK_URL = 'callback_url';
    const AUTH_TOKEN = 'callback_auth_token';
    /**
     * @var string
     */
    const CACHE_TAG = 'alma_insurance_subscription';
    /**
     * @var string
     */
    protected $_cacheTag = 'alma_insurance_subscription';
    /**
     * @var string
     */
    protected $_eventPrefix = 'alma_insurance_subscription';

    public function _construct()
    {
        $this->_init(\Alma\MonthlyPayments\Model\Insurance\ResourceModel\Subscription::class);
    }

    /**
     * @return string[]
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return int|null
     */
    public function getEntityId(): int
    {
        return $this->getDataByKey(self::ID_KEY);
    }

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->getDataByKey(self::ORDER_ID_KEY);
    }

    /**
     * @param int $orderId
     * @return void
     */
    public function setOrderId(int $orderId): void
    {
        $this->setData(self::ORDER_ID_KEY, $orderId);
    }

    /**
     * @return int
     */
    public function getOrderItemId(): int
    {
        return $this->getDataByKey(self::ORDER_ITEM_ID_KEY);
    }

    /**
     * @param int $orderItemId
     * @return void
     */
    public function setOrderItemId(int $orderItemId): void
    {
        $this->setData(self::ORDER_ITEM_ID_KEY, $orderItemId);
    }

    public function getName(): string
    {
        return $this->getDataByKey(self::SUBSCRIPTION_NAME_KEY);
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->setData(self::SUBSCRIPTION_NAME_KEY, $name);
    }

    public function getSubscriptionId(): string
    {
        return $this->getDataByKey(self::SUBSCRIPTION_ID_KEY);
    }

    /**
     * @param string $subscriptionId
     * @return void
     */
    public function setSubscriptionId(string $subscriptionId): void
    {
        $this->setData(self::SUBSCRIPTION_ID_KEY, $subscriptionId);
    }

    /**
     * @return int
     */
    public function getSubscriptionPrice(): int
    {
        return $this->getDataByKey(self::SUBSCRIPTION_PRICE_KEY);
    }

    /**
     * @param int $price
     * @return void
     */
    public function setSubscriptionPrice(int $price): void
    {
        $this->setData(self::SUBSCRIPTION_PRICE_KEY, $price);
    }

    /**
     * @return string
     */
    public function getProviderSubscriptionId(): string
    {
        return $this->getDataByKey(self::BROKER_SUBSCRIPTION_ID_KEY);
    }

    /**
     * @param string $providerSubscriptionId
     * @return void
     */
    public function setProviderSubscriptionId(string $providerSubscriptionId): void
    {
        $this->setData(self::BROKER_SUBSCRIPTION_ID_KEY, $providerSubscriptionId);
    }

    /**
     * @return string
     */
    public function getContractId(): string
    {
        return $this->getDataByKey(self::CONTRACT_ID_KEY);
    }

    /**
     * @param string $contractId
     * @return void
     */
    public function setContractId(string $contractId): void
    {
        $this->setData(self::CONTRACT_ID_KEY, $contractId);
    }

    /**
     * @return string
     */
    public function getCmsReference(): string
    {
        return $this->getDataByKey(self::CMS_REFERENCE_KEY);
    }

    /**
     * @param string $cmsReference
     * @return void
     */
    public function setCmsReference(string $cmsReference): void
    {
        $this->setData(self::CMS_REFERENCE_KEY, $cmsReference);
    }

    /**
     * @return string
     */
    public function getSubscriptionState(): string
    {
        return $this->getDataByKey(self::SUBSCRIPTION_STATE_KEY);
    }

    /**
     * @param string $state
     * @return void
     */
    public function setSubscriptionState(string $state): void
    {
        $this->setData(self::SUBSCRIPTION_STATE_KEY, $state);
    }

    /**
     * @return string
     */
    public function getSubscriptionMode(): string
    {
        return $this->getDataByKey(self::SUBSCRIPTION_MODE_KEY);
    }

    /**
     * @param string $mode
     * @return void
     */
    public function setSubscriptionMode(string $mode): void
    {
        $this->setData(self::SUBSCRIPTION_MODE_KEY, $mode);
    }

    /**
     * @return string|null
     */
    public function getCancellationDate(): ?string
    {
        return $this->getDataByKey(self::CANCELLATION_DATE_KEY);
    }

    /**
     * @param string|null $date
     * @return void
     */
    public function setCancellationDate(string $date = null): void
    {
        $this->setData(self::CANCELLATION_DATE_KEY, $date);
    }

    /**
     * @return string|null
     */
    public function getCancellationReason(): ?string
    {
        return $this->getDataByKey(self::CANCELLATION_REASON_KEY);
    }

    /**
     * @param string|null $reason
     * @return void
     */
    public function setCancellationReason(string $reason = null): void
    {
        $this->setData(self::CANCELLATION_REASON_KEY, $reason);
    }

    /**
     * @return bool
     */
    public function getIsRefunded(): bool
    {
        return $this->getDataByKey(self::IS_REFUND_KEY);
    }

    /**
     * @param bool $isRefunded
     * @return void
     */
    public function setIsRefunded(bool $isRefunded): void
    {
        $this->setData(self::IS_REFUND_KEY, $isRefunded);
    }

    /**
     * @return string
     */
    public function getCallbackUrl() : string
    {
        return $this->getDataByKey(self::CALLBACK_URL);
    }

    /**
     * @param string $callbackUrl
     * @return void
     */
    public function setCallbackUrl(string $callbackUrl): void
    {
        $this->setData(self::CALLBACK_URL, $callbackUrl);
    }

    /**
     * @return string
     */
    public function getAuthToken(): string
    {
        return $this->getDataByKey(self::AUTH_TOKEN);
    }

    /**
     * @param string $authToken
     * @return void
     */
    public function setAuthToken(string $authToken): void
    {
        $this->setData(self::AUTH_TOKEN, $authToken);
    }
}
