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
     * @param string | null $storeId
     *
     * @return string
     */
    public function getStoreId(string $storeId = null): string
    {
        $areaCode = $this->getAreaCode();

        if (!$storeId && $areaCode == self::AREA_FRONT) {
            $storeId = $this->storeResolver->getCurrentStoreId();
        }

        if (!$storeId && $areaCode == self::AREA_BACK) {
            $store = $this->request->getParam('store');
            $website = $this->request->getParam('website');
            if ($store) {
                $storeId = $store;
            } elseif ($website) {
                $storeId = $website;
            } else {
                $storeId = '0';
            }
        }

        return $storeId;
    }

    /**
     * @param string|null $scope
     *
     * @return string
     */
    public function getScope(string $scope = null): string
    {
        $areaCode = $this->getAreaCode();
        if (!$scope && $areaCode == self::AREA_FRONT) {
            $scope = ScopeInterface::SCOPE_STORES;
        }
        if (!$scope && $areaCode == self::AREA_BACK) {
            $store = $this->request->getParam('store');
            $website = $this->request->getParam('website');

            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

            if ($store) {
                $scope = ScopeInterface::SCOPE_STORES;
            } elseif ($website) {
                $scope = ScopeInterface::SCOPE_WEBSITES;
            }

        }
        return $scope;
    }

}
