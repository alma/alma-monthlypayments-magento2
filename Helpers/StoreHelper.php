<?php

namespace Alma\MonthlyPayments\Helpers;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class StoreHelper extends AbstractHelper
{
    const AREA_FRONT = 'frontend';
    const AREA_BACK = 'adminhtml';
    const AREA_API = 'webapi_rest';
    const AREA_CRON = 'crontab';
    const AREA_GRAPHQL = 'graphql';

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var State
     */
    private $state;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var StoreManagerInterface
     */
    private $storeManagement;

    /**
     * @param Context $context
     * @param State $state
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManagement
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        State $state,
        StoreManagerInterface $storeManagement,
        RequestInterface $request,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->state = $state;
        $this->request = $request;
        $this->storeManagement = $storeManagement;
    }

    /**
     * @return string
     */
    public function getAreaCode(): string
    {
        try {
            return $this->state->getAreaCode();
        } catch (LocalizedException $e) {
            $this->logger->error('Error in getAreaCode', [$e->getMessage()]);
            return 'frontend';
        }
    }

    /**
     *
     * @return string
     */
    public function getStoreId(): string
    {
        $areaCode = $this->getAreaCode();
        if ($areaCode === self::AREA_BACK) {
            return $this->backStoreId();
        }
        if (!in_array($areaCode, [self::AREA_FRONT, self::AREA_API, self::AREA_CRON, self::AREA_GRAPHQL])) {
            $this->logger->warning('Error in Area Code', [$areaCode]);
        }

        return $this->storeManagement->getStore()->getId();
    }

    /**
     *
     * @return string
     */
    public function getScope(): string
    {
        $areaCode = $this->getAreaCode();
        if ($areaCode == self::AREA_BACK) {
            return $this->backScopeCode();
        }
        return ScopeInterface::SCOPE_STORES;
    }

    /**
     * @return string
     */
    private function backScopeCode(): string
    {
        $store = $this->request->getParam('store');
        if ($store) {
            return ScopeInterface::SCOPE_STORES;
        }
        $website = $this->request->getParam('website');
        if ($website) {
            return ScopeInterface::SCOPE_WEBSITES;
        }
        return ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
    }

    /**
     * @return string
     */
    private function backStoreId(): string
    {
        $store = $this->request->getParam('store');
        if ($store) {
            return $store;
        }
        $website = $this->request->getParam('website');
        if ($website) {
            return $website;
        }
        return '0';
    }
}
