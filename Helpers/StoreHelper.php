<?php

namespace Alma\MonthlyPayments\Helpers;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreResolver;

class StoreHelper extends AbstractHelper
{
    const AREA_FRONT = 'frontend';
    const AREA_BACK = 'adminhtml';
    const AREA_API = 'webapi_rest';

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
     * @var StoreResolver
     */
    private $storeResolver;

    /**
     * @param Context $context
     * @param State $state
     * @param StoreResolver $storeResolver
     * @param RequestInterface $request
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        State $state,
        StoreResolver $storeResolver,
        RequestInterface $request,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->state = $state;
        $this->request = $request;
        $this->storeResolver = $storeResolver;
    }

    /**
     * @return string
     */
    public function getAreaCode(): string
    {
        try {
            return $this->state->getAreaCode();
        } catch (LocalizedException $e) {
            $this->logger->info('Error in getAreaCode', [$e->getMessage()]);
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
        switch ($areaCode) {
            case self::AREA_FRONT:
            case self::AREA_API:
                return $this->storeResolver->getCurrentStoreId();
            case self::AREA_BACK:
                return $this->backStoreId();
            default:
                $this->logger->error('Error in Area Code', [$areaCode]);
                return '0';
        }
    }

    /**
     *
     * @return string
     */
    public function getScope(): string
    {
        $areaCode = $this->getAreaCode();
        switch ($areaCode) {
            case self::AREA_BACK:
                return $this->backScopdeCode();
            default:
                return ScopeInterface::SCOPE_STORES;
        }

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
